<?php
namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class HumanVerifier
{
    // Minimum seconds a human takes to fill a form
    const MIN_FORM_TIME = 1;

    // ── Generate a new challenge with signed HMAC token ────────────────
    public static function generate(string $formKey = 'default'): array
    {
        $a   = random_int(1, 9);
        $b   = random_int(1, 9);
        $ops = [
            ['label' => "{$a} + {$b}", 'answer' => $a + $b],
            ['label' => "{$a} × {$b}", 'answer' => $a * $b],
            ['label' => max($a,$b)." − ".min($a,$b), 'answer' => max($a,$b) - min($a,$b)],
        ];
        $op = $ops[array_rand($ops)];

        $time   = time();
        $ans    = (string)$op['answer'];
        $secret = config('app.key') ?: 'skillspot-lms-secure-key';
        $sig    = hash_hmac('sha256', "{$time}:{$ans}:{$formKey}", $secret);
        $token  = base64_encode("{$time}:{$ans}:{$sig}");

        // Also save in Session for backward compatibility
        try {
            Session::put("hv_{$formKey}", [
                'question' => $op['label'],
                'answer'   => $ans,
                'token'    => $token,
                'at'       => $time,
            ]);
        } catch (\Throwable $e) {}

        return [
            'question' => $op['label'],
            'token'    => $token,
        ];
    }

    // ── Check if verification is enabled via settings ─────────────────
    public static function isEnabled(string $formType = 'login'): bool
    {
        $key = $formType === 'register' ? 'human_verify_register' : 'human_verify_login';
        try {
            return \App\Models\Setting::get($key, '1') === '1';
        } catch (\Throwable $e) {
            return true;
        }
    }

    // ── Verify all checks ──────────────────────────────────────────────
    public static function verify(Request $request, string $formKey = 'default'): array
    {
        $formType = (str_contains($formKey, 'reg') || str_contains($formKey, 'register')) ? 'register' : 'login';
        if (!static::isEnabled($formType)) {
            return ['pass' => true, 'reason' => ''];
        }

        // 1. Honeypot check — must be empty
        if (!empty($request->input('_email_confirm'))
            || !empty($request->input('_website'))
            || !empty($request->input('_phone_confirm'))) {
            return ['pass' => false, 'reason' => 'Bot activity detected. Please try again.'];
        }

        // 2. Math answer must be present
        $givenAnswer = trim($request->input('_hv_answer', ''));
        if ($givenAnswer === '') {
            return ['pass' => false, 'reason' => 'Please answer the human verification question.'];
        }

        // 3. Token check (Stateless HMAC verification + Session fallback)
        $token = $request->input('_hv_token', '');
        $expectedAnswer = null;
        $genTime = null;

        if (!empty($token)) {
            $decoded = base64_decode($token, true);
            if ($decoded && str_contains($decoded, ':')) {
                $parts = explode(':', $decoded, 3);
                if (count($parts) === 3) {
                    [$timeStr, $ansStr, $sigStr] = $parts;
                    $secret = config('app.key') ?: 'skillspot-lms-secure-key';
                    $validSig = hash_hmac('sha256', "{$timeStr}:{$ansStr}:{$formKey}", $secret);
                    
                    if (hash_equals($validSig, $sigStr)) {
                        $expectedAnswer = $ansStr;
                        $genTime = (int)$timeStr;
                    }
                }
            }
        }

        // Fallback to session if HMAC token not decoded
        if ($expectedAnswer === null) {
            try {
                $sess = Session::get("hv_{$formKey}");
                if ($sess) {
                    $expectedAnswer = $sess['answer'] ?? null;
                    $genTime = $sess['at'] ?? null;
                }
            } catch (\Throwable $e) {}
        }

        if ($expectedAnswer === null) {
            return ['pass' => false, 'reason' => 'Verification expired. Please refresh and try again.'];
        }

        // 4. Verify math calculation
        if ($givenAnswer !== (string)$expectedAnswer) {
            return ['pass' => false, 'reason' => 'Verification answer is incorrect. Please try again.'];
        }

        // 5. Time check — max 1 hour valid
        if ($genTime && (time() - $genTime > 3600)) {
            return ['pass' => false, 'reason' => 'Verification question timed out. Please refresh and try again.'];
        }

        return ['pass' => true, 'reason' => ''];
    }

    // ── Quick static check (returns bool) ────────────────────────────
    public static function passes(Request $request, string $formKey = 'default'): bool
    {
        return self::verify($request, $formKey)['pass'];
    }
}

