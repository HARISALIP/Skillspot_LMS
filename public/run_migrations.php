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

// Force reload fresh .env file
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createMutable(__DIR__ . '/..');
    $dotenv->load();
}

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Purge cached DB connection & apply current .env settings in memory
try {
    Illuminate\Support\Facades\Artisan::call('config:clear');
    
    $host = env('DB_HOST', 'localhost');
    $port = env('DB_PORT', '3306');
    $dbname = env('DB_DATABASE', 'u265225504_skillspotlms');
    $user = env('DB_USERNAME', 'u265225504_skillspossr12');
    $pass = env('DB_PASSWORD', 'Casey@@!1');

    config([
        'database.default' => 'mysql',
        'database.connections.mysql.host' => $host,
        'database.connections.mysql.port' => $port,
        'database.connections.mysql.database' => $dbname,
        'database.connections.mysql.username' => $user,
        'database.connections.mysql.password' => $pass,
    ]);

    Illuminate\Support\Facades\DB::purge('mysql');
    Illuminate\Support\Facades\DB::reconnect('mysql');
} catch (\Throwable $e) {}

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
        .badge-info { background: #0284c7; color: #e0f2fe; }
        pre { background: #0f172a; padding: 15px; border-radius: 8px; overflow-x: auto; color: #38bdf8; font-size: 13px; border: 1px solid #334155; }
        a.btn { display: inline-block; background: #2563eb; color: #fff; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; margin-top: 15px; }
    </style>
</head>
<body>

<div class="card">
    <h1>🚀 Skillspot LMS Web Migration Runner</h1>
    <p>Connected Database: <strong><?php echo htmlspecialchars(config('database.connections.mysql.database')); ?></strong> (User: <?php echo htmlspecialchars(config('database.connections.mysql.username')); ?>)</p>
</div>

<div class="card">
    <h2>1. Database Migrations (`php artisan migrate --force`)</h2>
    <?php
    try {
        Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        echo "<span class='badge badge-success'>✅ Migrations Executed Successfully!</span>";
        echo "<pre>" . htmlspecialchars(Illuminate\Support\Facades\Artisan::output()) . "</pre>";
    } catch (\Throwable $e) {
        echo "<span class='badge badge-error'>❌ Migration Error: " . htmlspecialchars($e->getMessage()) . "</span>";
    }
    ?>

    <h2>2. Super Admin User Creation</h2>
    <?php
    try {
        $email = env('SUPER_ADMIN_EMAIL', 'skillspot.in@gmail.com');
        $password = env('SUPER_ADMIN_PASSWORD', 'SkillSpot#2026@Secure');

        // 1. Ensure roles exist
        $roles = ['super-admin', 'admin', 'teacher', 'student', 'vendor'];
        foreach ($roles as $r) {
            try { Spatie\Permission\Models\Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']); } catch (\Throwable $ex) {}
        }

        // 2. Ensure permissions exist
        $permissions = [
            'view dashboard', 'view users', 'create users', 'edit users', 'delete users', 'assign roles',
            'view courses', 'create courses', 'edit courses', 'delete courses', 'publish courses',
            'view enrollments', 'manage enrollments', 'view payments', 'issue refunds',
            'view certificates', 'issue certificates', 'revoke certificates', 'view settings', 'edit settings',
            'view roles', 'create roles', 'edit roles', 'delete roles', 'view reports'
        ];
        foreach ($permissions as $p) {
            try { Spatie\Permission\Models\Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']); } catch (\Throwable $ex) {}
        }

        // 3. Assign permissions to super-admin & admin
        try {
            $allPermissions = Spatie\Permission\Models\Permission::all();
            $saRole = Spatie\Permission\Models\Role::where('name', 'super-admin')->first();
            if ($saRole) { $saRole->syncPermissions($allPermissions); }
            $aRole = Spatie\Permission\Models\Role::where('name', 'admin')->first();
            if ($aRole) { $aRole->syncPermissions($allPermissions); }
        } catch (\Throwable $ex) {}

        // 4. Create/Update Super Admin user
        $user = App\Models\User::updateOrCreate(
            ['email' => $email],
            [
                'name'              => 'Skillspot Super Admin',
                'password'          => Illuminate\Support\Facades\Hash::make($password),
                'portal_access'     => 'both',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );
        try { $user->syncRoles(['super-admin', 'admin']); } catch (\Throwable $ex) {}

        echo "<span class='badge badge-success'>✅ Super Admin Account Active & Configured!</span>";
        echo "<pre>Email: {$email}\nPassword: {$password}\nName: {$user->name}\nRoles: super-admin, admin</pre>";
    } catch (\Throwable $e) {
        echo "<span class='badge badge-error'>❌ Super Admin Creation Error: " . htmlspecialchars($e->getMessage()) . "</span>";
    }
    ?>

    <h2>3. Database Seeder (`php artisan db:seed --force`)</h2>
    <?php
    try {
        Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
        echo "<span class='badge badge-success'>✅ Database Seeded Successfully!</span>";
        echo "<pre>" . htmlspecialchars(Illuminate\Support\Facades\Artisan::output()) . "</pre>";
    } catch (\Throwable $e) {
        echo "<span class='badge badge-info'>ℹ️ Seeder status: " . htmlspecialchars($e->getMessage()) . "</span>";
    }
    ?>

    <div style="text-align: center; margin-top: 30px;">
        <a href="/login" class="btn">👉 Click Here to Go to Login Page</a>
    </div>
</div>

</body>
</html>
