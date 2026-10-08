<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Guard: only run if settings table exists
        try {
            if (!Schema::hasTable('settings')) return;
        } catch (\Exception $e) {
            return;
        }

        $this->loadSettingsFromDb();

        // Redirect authenticated users visiting /login or /register
        // to their correct dashboard instead of generic /dashboard
        RedirectIfAuthenticated::redirectUsing(function ($request) {
            $user = Auth::user();
            if (!$user) return '/';
            if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
                return route('admin.dashboard');
            }
            return route('student.dashboard');
        });
    }

    private function loadSettingsFromDb(): void
    {
        try {
            $settings = Cache::rememberForever('all_settings', function () {
                return \App\Models\Setting::all()->pluck('value', 'key')->toArray();
            });
        } catch (\Exception $e) {
            return;
        }

        $s = fn(string $key, string $fallback = '') => $settings[$key] ?? $fallback;

        // ── App / General ──────────────────────────────────────────────
        if ($url = $s('app_url')) {
            Config::set('app.url', rtrim($url, '/'));
            URL::forceRootUrl(rtrim($url, '/'));
            if (str_starts_with($url, 'https://')) {
                URL::forceScheme('https');
            }
        }

        if ($name = $s('site_name')) {
            Config::set('app.name', $name);
        }

        if ($s('maintenance_mode') === '1') {
            Config::set('app.maintenance', true);
        }

        // ── Mail ───────────────────────────────────────────────────────
        if ($host = $s('mail_host')) {
            Config::set('mail.mailers.smtp.host',       $host);
            Config::set('mail.mailers.smtp.port',       (int) $s('mail_port', '587'));
            Config::set('mail.mailers.smtp.username',   $s('mail_username'));
            Config::set('mail.mailers.smtp.password',   $s('mail_password'));
            Config::set('mail.mailers.smtp.encryption', $s('mail_encryption', 'tls'));
            Config::set('mail.from.address',            $s('mail_from_address'));
            Config::set('mail.from.name',               $s('mail_from_name', config('app.name')));
            Config::set('mail.default',                 $s('mail_mailer', 'smtp'));
        }

        // ── Cloudflare R2 Storage ──────────────────────────────────────
        if ($endpoint = $s('r2_endpoint')) {
            Config::set('filesystems.disks.r2', [
                'driver'                  => 's3',
                'key'                     => $s('r2_access_key_id'),
                'secret'                  => $s('r2_secret_access_key'),
                'region'                  => 'auto',
                'bucket'                  => $s('r2_bucket', 'Skillspot'),
                'url'                     => $s('r2_public_url'),
                'endpoint'                => $endpoint,
                'use_path_style_endpoint' => true,
            ]);
            if ($s('storage_driver') === 'r2') {
                Config::set('filesystems.default', 'r2');
            }
        }

        // ── Session lifetime ───────────────────────────────────────────
        if ($lifetime = $s('session_lifetime_min')) {
            Config::set('session.lifetime', (int) $lifetime);
        }
    }
}
