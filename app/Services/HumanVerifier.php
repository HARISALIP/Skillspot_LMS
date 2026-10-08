<?php
namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class HumanVerifier
{
    // Minimum seconds a human takes to fill a form
    const MIN_FORM_TIME = 3;

    // ── Generate a new challenge and store in session ──────────────────
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

        $token = bin2hex(random_bytes(16));

        Session::put("hv_{$formKey}", [
            'question' => $op['label'],
            'answer'   => (string) $op['answer'],
            'token'    => $token,
            'at'       => time(),
        ]);

        return [
            'question' => $op['label'],
            'token'    => $token,
        ];
    }

    // ── Check if verification is enabled via settings ─────────────────
    public static function isEnabled(string $formType = 'login'): bool
    {
        $key = $formType === 'register' ? 'human_verify_register' : 'human_verify_login';
        return \App\Models\Setting::get($key, '1') === '1';
    }

    // ── Verify all checks ──────────────────────────────────────────────
    public static function verify(Request $request, string $formKey = 'default'): array
    {
        // If disabled by admin settings, always pass
        // formKey may be 'login', 'register', 'portal_login_*', 'portal_reg_*'
        $formType = (str_contains($formKey, 'reg') || str_contains($formKey, 'register')) ? 'register' : 'login';
        if (!static::isEnabled($formType)) {
            Session::forget("hv_{$formKey}");
            return ['pass' => true, 'reason' => ''];
        }

        $session = Session::pull("hv_{$formKey}");

        // 1. Session exists
        if (!$session) {
            return ['pass' => false, 'reason' => 'Session expired. Please refresh and try again.'];
        }

        // 2. Honeypot — must be empty
        if (!empty($request->input('_email_confirm'))
            || !empty($request->input('_website'))
            || !empty($request->input('_phone_confirm'))) {
            return ['pass' => false, 'reason' => 'Verification failed. Please try again.'];
        }

        // 3. Time check — too fast = bot
        $elapsed = time() - ($session['at'] ?? 0);
        if ($elapsed < self::MIN_FORM_TIME) {
            return ['pass' => false, 'reason' => 'Form submitted too quickly. Please try again.'];
        }

        // 4. JS token must match
        if (empty($request->input('_hv_token'))
            || $request->input('_hv_token') !== $session['token']) {
            return ['pass' => false, 'reason' => 'JavaScript verification failed. Please enable JavaScript.'];
        }

        // 5. Interaction proof — JS must have recorded activity
        $interaction = $request->input('_hv_interact', '0');
        if ((int)$interaction < 1) {
            return ['pass' => false, 'reason' => 'No human interaction detected. Please interact with the form.'];
        }

        // 6. Math answer
        $givenAnswer = trim($request->input('_hv_answer', ''));
        if ($givenAnswer !== $session['answer']) {
            return ['pass' => false, 'reason' => 'Verification answer is incorrect. Please try again.'];
        }

        return ['pass' => true, 'reason' => ''];
    }

    // ── Quick static check (returns bool) ────────────────────────────
    public static function passes(Request $request, string $formKey = 'default'): bool
    {
        return self::verify($request, $formKey)['pass'];
    }
}
