<?php
namespace App\Services;

use App\Models\Setting;
use App\Models\Otp;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsOtpService
{
    private string $apiKey;
    private string $baseUrl = 'https://www.fast2sms.com/dev/bulkV2';

    public function __construct()
    {
        $this->apiKey = Setting::get('fast2sms_api_key', '');
    }

    // ── Send OTP via Fast2SMS Quick SMS ────────────────────────────────
    public function sendOtp(string $phone, string $purpose = 'register'): array
    {
        $phone = $this->normalizePhone($phone);
        $code  = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Invalidate previous OTPs
        Otp::where('identifier', $phone)
           ->where('type', 'phone')
           ->where('purpose', $purpose)
           ->update(['used' => true]);

        // Save new OTP
        Otp::create([
            'identifier' => $phone,
            'type'       => 'phone',
            'purpose'    => $purpose,
            'otp'        => $code,
            'expires_at' => now()->addMinutes(10),
        ]);

        // Send via Fast2SMS Quick SMS (no DLT needed)
        return $this->send($phone, $code);
    }

    // ── Verify OTP ─────────────────────────────────────────────────────
    public function verifyOtp(string $phone, string $code, string $purpose = 'register'): bool
    {
        $phone = $this->normalizePhone($phone);

        $record = Otp::where('identifier', $phone)
            ->where('type', 'phone')
            ->where('purpose', $purpose)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$record || $record->otp !== $code) {
            return false;
        }

        $record->update(['used' => true]);
        return true;
    }

    // ── Internal: call Fast2SMS API ────────────────────────────────────
    private function send(string $phone, string $code): array
    {
        $siteName = Setting::get('site_name', 'Skillspot');
        $message  = "Your {$siteName} OTP is {$code}. Valid for 10 minutes. Do not share.";

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'authorization' => $this->apiKey,
                    'Cache-Control' => 'no-cache',
                ])
                ->post($this->baseUrl, [
                    'route'    => 'q',           // Quick SMS — no DLT required
                    'message'  => $message,
                    'language' => 'english',
                    'flash'    => 0,
                    'numbers'  => $phone,
                ]);

            $body = $response->json();

            if ($response->successful() && ($body['return'] ?? false)) {
                Log::info('Fast2SMS OTP sent', ['phone' => $phone, 'request_id' => $body['request_id'] ?? null]);
                return ['success' => true, 'message' => 'OTP sent successfully'];
            }

            $errorMsg = $body['message'] ?? 'Failed to send OTP';
            Log::warning('Fast2SMS OTP failed', ['phone' => $phone, 'response' => $body]);
            return ['success' => false, 'message' => $errorMsg];

        } catch (\Exception $e) {
            Log::error('Fast2SMS exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'SMS service unavailable. Please try again.'];
        }
    }

    // ── Normalize phone — strip +91, spaces, dashes ────────────────────
    public static function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[\s\-\(\)]/', '', $phone);
        $phone = ltrim($phone, '+');
        if (str_starts_with($phone, '91') && strlen($phone) === 12) {
            $phone = substr($phone, 2);
        }
        return $phone;
    }

    // ── Check if SMS OTP is ready to use ──────────────────────────────
    public static function isReady(): bool
    {
        return !empty(Setting::get('fast2sms_api_key', ''));
    }
}
