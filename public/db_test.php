<?php
/**
 * Skillspot LMS - Live Server Database & Environment Diagnostic Tool
 * Upload this file to your live server's public or root folder to test DB connection.
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skillspot LMS - Live Server DB Diagnostic</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 30px; }
        .card { background: #1e293b; border-radius: 12px; padding: 24px; max-width: 800px; margin: 0 auto 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); border: 1px solid #334155; }
        h1 { color: #38bdf8; margin-top: 0; font-size: 24px; }
        h2 { color: #94a3b8; font-size: 18px; border-bottom: 1px solid #334155; padding-bottom: 8px; margin-top: 20px; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 9999px; font-weight: 600; font-size: 14px; margin-bottom: 15px; }
        .badge-success { background: #059669; color: #ecfdf5; }
        .badge-error { background: #dc2626; color: #fef2f2; }
        .badge-warn { background: #d97706; color: #fffbeb; }
        pre { background: #0f172a; padding: 15px; border-radius: 8px; overflow-x: auto; color: #e2e8f0; font-size: 13px; border: 1px solid #334155; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { text-align: left; padding: 10px; border-bottom: 1px solid #334155; font-size: 14px; }
        th { color: #94a3b8; }
        .tip { background: #1e1b4b; border-left: 4px solid #6366f1; padding: 12px; margin-top: 15px; border-radius: 0 8px 8px 0; }
    </style>
</head>
<body>

<div class="card">
    <h1>🔍 Skillspot LMS - Live Server Database Diagnostic</h1>
    <p>This tool diagnoses your live server PHP configuration, MySQL PDO extension, and database credentials.</p>
</div>

<div class="card">
    <h2>1. PHP Environment & Extensions</h2>
    <table>
        <tr>
            <td>PHP Version:</td>
            <td><strong><?php echo PHP_VERSION; ?></strong> <?php echo (version_compare(PHP_VERSION, '8.2.0', '>=') ? '✅ (Compatible)' : '⚠️ (Requires PHP >= 8.2)'); ?></td>
        </tr>
        <tr>
            <td>PDO Extension:</td>
            <td><?php echo extension_loaded('pdo') ? '✅ Enabled' : '❌ Disabled (Enable in cPanel -> Select PHP Version)'; ?></td>
        </tr>
        <tr>
            <td>PDO MySQL Driver:</td>
            <td><?php echo extension_loaded('pdo_mysql') ? '✅ Enabled' : '❌ Disabled (Enable pdo_mysql extension)'; ?></td>
        </tr>
        <tr>
            <td>OpenSSL Extension:</td>
            <td><?php echo extension_loaded('openssl') ? '✅ Enabled' : '❌ Disabled'; ?></td>
        </tr>
        <tr>
            <td>Mbstring Extension:</td>
            <td><?php echo extension_loaded('mbstring') ? '✅ Enabled' : '❌ Disabled'; ?></td>
        </tr>
    </table>
</div>

<div class="card">
    <h2>2. `.env` File Reading Test</h2>
    <?php
    $envPath = __DIR__ . '/.env';
    if (!file_exists($envPath)) {
        $envPath = __DIR__ . '/../.env';
    }

    if (file_exists($envPath)) {
        echo "<span class='badge badge-success'>✅ .env file found at: " . htmlspecialchars($envPath) . "</span>";
        $envLines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $envData = [];
        foreach ($envLines as $line) {
            if (str_starts_with(trim($line), '#')) continue;
            if (str_contains($line, '=')) {
                list($key, $val) = explode('=', $line, 2);
                $envData[trim($key)] = trim($val, "\"'\t ");
            }
        }
        $dbConn = $envData['DB_CONNECTION'] ?? 'not set';
        $dbHost = $envData['DB_HOST'] ?? 'not set';
        $dbPort = $envData['DB_PORT'] ?? '3306';
        $dbName = $envData['DB_DATABASE'] ?? 'not set';
        $dbUser = $envData['DB_USERNAME'] ?? 'not set';
        $dbPass = $envData['DB_PASSWORD'] ?? '';

        echo "<table>";
        echo "<tr><td>DB_CONNECTION:</td><td><strong>" . htmlspecialchars($dbConn) . "</strong></td></tr>";
        echo "<tr><td>DB_HOST:</td><td><strong>" . htmlspecialchars($dbHost) . "</strong></td></tr>";
        echo "<tr><td>DB_PORT:</td><td><strong>" . htmlspecialchars($dbPort) . "</strong></td></tr>";
        echo "<tr><td>DB_DATABASE:</td><td><strong>" . htmlspecialchars($dbName) . "</strong></td></tr>";
        echo "<tr><td>DB_USERNAME:</td><td><strong>" . htmlspecialchars($dbUser) . "</strong></td></tr>";
        echo "<tr><td>DB_PASSWORD:</td><td>" . (!empty($dbPass) ? '****** (Set)' : '⚠️ Empty') . "</td></tr>";
        echo "</table>";
    } else {
        echo "<span class='badge badge-error'>❌ .env file NOT FOUND! Copy .env.example to .env and configure your live credentials.</span>";
        $dbConn = $dbHost = $dbPort = $dbName = $dbUser = $dbPass = null;
    }
    ?>
</div>

<div class="card">
    <h2>3. Live Database Connection Test</h2>
    <?php
    if (isset($dbConn) && $dbConn === 'sqlite') {
        echo "<span class='badge badge-warn'>⚠️ Currently configured to use SQLite.</span><br>";
        try {
            $sqlitePath = $dbName;
            if (!str_starts_with($sqlitePath, '/') && !str_starts_with($sqlitePath, 'C:')) {
                $sqlitePath = __DIR__ . '/' . $sqlitePath;
            }
            $pdo = new PDO("sqlite:" . $sqlitePath);
            echo "<span class='badge badge-success'>✅ SQLite Database Connected Successfully!</span>";
        } catch (PDOException $e) {
            echo "<span class='badge badge-error'>❌ SQLite Error: " . htmlspecialchars($e->getMessage()) . "</span>";
        }
    } elseif (!empty($dbHost) && $dbHost !== 'not set') {
        try {
            // First try connecting to MySQL server
            $dsn = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);
            echo "<span class='badge badge-success'>✅ MySQL Server Connection Successful! ($dbHost:$dbPort)</span><br><br>";

            // Next check if target database exists
            try {
                $pdo->exec("USE `$dbName`");
                echo "<span class='badge badge-success'>✅ Database '$dbName' selected successfully!</span><br><br>";

                // Check tables count
                $stmt = $pdo->query("SHOW TABLES");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                echo "<strong>Tables found in database (" . count($tables) . "):</strong>";
                if (count($tables) > 0) {
                    echo "<pre>" . implode(", ", array_slice($tables, 0, 20)) . (count($tables) > 20 ? "..." : "") . "</pre>";
                    if (in_array('users', $tables)) {
                        $userStmt = $pdo->query("SELECT email, name FROM users LIMIT 5");
                        $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);
                        echo "<strong>Sample Users in 'users' table:</strong>";
                        echo "<pre>" . print_r($users, true) . "</pre>";
                    } else {
                        echo "<span class='badge badge-warn'>⚠️ 'users' table not found! Run: php artisan migrate --force</span>";
                    }
                } else {
                    echo "<br><span class='badge badge-warn'>⚠️ Database is empty (0 tables)! Run: php artisan migrate --force --seed</span>";
                }
            } catch (PDOException $e) {
                echo "<span class='badge badge-error'>❌ Database '$dbName' access failed: " . htmlspecialchars($e->getMessage()) . "</span>";
                echo "<div class='tip'>💡 <strong>Tip for cPanel:</strong> In cPanel -> MySQL Databases, ensure user '$dbUser' has been added to database '$dbName' with ALL PRIVILEGES.</div>";
            }
        } catch (PDOException $e) {
            echo "<span class='badge badge-error'>❌ MySQL Connection Failed: " . htmlspecialchars($e->getMessage()) . "</span>";
            echo "<div class='tip'>💡 <strong>Troubleshooting Steps:</strong><br>
            1. Verify <code>DB_HOST</code> (Try <code>127.0.0.1</code> instead of <code>localhost</code> or vice-versa).<br>
            2. Double-check <code>DB_USERNAME</code> and <code>DB_PASSWORD</code>.<br>
            3. On cPanel hosting, database names and usernames usually have a prefix (e.g., <code>cpaneluser_dbname</code>).
            </div>";
        }
    } else {
        echo "<span class='badge badge-warn'>Please configure your .env file with live MySQL database details.</span>";
    }
    ?>
</div>

</body>
</html>
