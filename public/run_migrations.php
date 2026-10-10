<?php
/**
 * Skillspot LMS - Web Migration Runner
 * Visit: https://lms.skillspot.in/run_migrations.php?key=SkillSpot2026
 */

$secretKey = 'SkillSpot2026';

if (($_GET['key'] ?? '') !== $secretKey) {
    http_response_code(403);
    die('<!DOCTYPE html><html><body style="font-family:sans-serif;background:#0f172a;color:#f8fafc;padding:40px;text-align:center;"><h2 style="color:#ef4444;">403 Forbidden</h2><p>Invalid security key. Pass <code>?key=SkillSpot2026</code> in the URL.</p></body></html>');
}

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Skillspot LMS - Web Migration Runner</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 30px; }
        .card { background: #1e293b; border-radius: 12px; padding: 24px; max-width: 800px; margin: 0 auto 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); border: 1px solid #334155; }
        h1 { color: #38bdf8; margin-top: 0; font-size: 24px; }
        h2 { color: #94a3b8; font-size: 18px; border-bottom: 1px solid #334155; padding-bottom: 8px; margin-top: 20px; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 9999px; font-weight: 600; font-size: 14px; margin-bottom: 15px; }
        .badge-success { background: #059669; color: #ecfdf5; }
        .badge-error { background: #dc2626; color: #fef2f2; }
        pre { background: #0f172a; padding: 15px; border-radius: 8px; overflow-x: auto; color: #38bdf8; font-size: 13px; border: 1px solid #334155; }
        a.btn { display: inline-block; background: #2563eb; color: #fff; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; margin-top: 15px; }
    </style>
</head>
<body>

<div class="card">
    <h1>🚀 Skillspot LMS Web Migration Runner</h1>
    <p>Running database migrations and seeder automatically from web browser...</p>
</div>

<div class="card">
    <h2>1. Config Clear</h2>
    <?php
    try {
        Illuminate\Support\Facades\Artisan::call('config:clear');
        echo "<span class='badge badge-success'>✅ Config Cleared</span>";
        echo "<pre>" . htmlspecialchars(Illuminate\Support\Facades\Artisan::output()) . "</pre>";
    } catch (\Throwable $e) {
        echo "<span class='badge badge-error'>❌ Config Clear Error: " . htmlspecialchars($e->getMessage()) . "</span>";
    }
    ?>

    <h2>2. Database Migrations (`php artisan migrate --force`)</h2>
    <?php
    try {
        Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        echo "<span class='badge badge-success'>✅ Migrations Executed Successfully!</span>";
        echo "<pre>" . htmlspecialchars(Illuminate\Support\Facades\Artisan::output()) . "</pre>";
    } catch (\Throwable $e) {
        echo "<span class='badge badge-error'>❌ Migration Error: " . htmlspecialchars($e->getMessage()) . "</span>";
    }
    ?>

    <h2>3. Database Seeder (`php artisan db:seed --force`)</h2>
    <?php
    try {
        Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
        echo "<span class='badge badge-success'>✅ Database Seeded Successfully!</span>";
        echo "<pre>" . htmlspecialchars(Illuminate\Support\Facades\Artisan::output()) . "</pre>";
    } catch (\Throwable $e) {
        echo "<span class='badge badge-error'>❌ Seeding Error: " . htmlspecialchars($e->getMessage()) . "</span>";
    }
    ?>

    <h2>4. Storage Link (`php artisan storage:link`)</h2>
    <?php
    try {
        Illuminate\Support\Facades\Artisan::call('storage:link');
        echo "<span class='badge badge-success'>✅ Storage Link Connected</span>";
        echo "<pre>" . htmlspecialchars(Illuminate\Support\Facades\Artisan::output()) . "</pre>";
    } catch (\Throwable $e) {
        echo "<span class='badge badge-success'>ℹ️ Storage Link info: " . htmlspecialchars($e->getMessage()) . "</span>";
    }
    ?>

    <div style="text-align: center; margin-top: 30px;">
        <a href="/login" class="btn">👉 Click Here to Go to Login Page</a>
    </div>
</div>

</body>
</html>
