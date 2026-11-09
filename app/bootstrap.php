<?php
declare(strict_types=1);

// Set RADIANCE_STORAGE to a writable directory outside the public web root in production.
const ROOT = __DIR__ . '/..';
define('STORAGE', getenv('RADIANCE_STORAGE') ?: (getenv('VERCEL') ? '/tmp/radiance-storage' : ROOT . '/storage'));

if (!is_dir(STORAGE)) {
    mkdir(STORAGE, 0700, true);
}
if (!is_dir(STORAGE . '/uploads')) {
    mkdir(STORAGE . '/uploads', 0700, true);
}

if (PHP_SAPI !== 'cli') {
    if (getenv('VERCEL') && is_dir('/tmp')) {
        session_save_path('/tmp');
    }
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || !empty(getenv('VERCEL'));
    session_set_cookie_params([
        'httponly' => true,
        'secure'   => $secure,
        'samesite' => 'Lax',
        'path'     => '/',
    ]);
    session_start();
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('X-Frame-Options: SAMEORIGIN');
}

function db(): PDO
{
    static $pdo;
    if ($pdo) {
        return $pdo;
    }

    if (!extension_loaded('pdo_sqlite')) {
        $msg = "SQLite (pdo_sqlite) is not enabled in PHP.\n"
             . "In XAMPP on Windows: open C:\\xampp\\php\\php.ini and remove the semicolon (;) in front of:\n"
             . "  extension=pdo_sqlite\n"
             . "  extension=sqlite3\n"
             . "Save php.ini, restart Apache in the XAMPP Control Panel, and refresh this page.";
        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, $msg . "\n");
            exit(1);
        }
        http_response_code(500);
        exit(
            '<div style="font:15px/1.6 system-ui,sans-serif;max-width:640px;margin:60px auto;padding:28px;background:#0d1b2a;color:#fdf1df;border:2px solid #e5631a;border-radius:14px">'
            . '<h2 style="margin-top:0;color:#f2b830">Enable SQLite in XAMPP</h2>'
            . '<pre style="white-space:pre-wrap;background:#16263b;padding:16px;border-radius:10px;border:1px solid #2b425f;color:#ffffff">'
            . htmlspecialchars($msg)
            . '</pre></div>'
        );
    }

    $pdo = new PDO('sqlite:' . STORAGE . '/radiance.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys=ON; PRAGMA busy_timeout=5000');

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY,
            name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            role TEXT NOT NULL,
            category TEXT,
            state TEXT,
            mood TEXT,
            account_status TEXT NOT NULL DEFAULT 'Approved',
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS submissions (
            id INTEGER PRIMARY KEY,
            artist_name TEXT NOT NULL,
            email TEXT NOT NULL,
            title TEXT NOT NULL,
            state TEXT NOT NULL,
            genre TEXT NOT NULL,
            description TEXT,
            filename TEXT NOT NULL,
            mime TEXT NOT NULL,
            size INTEGER NOT NULL,
            status TEXT NOT NULL DEFAULT 'Pending',
            token_hash TEXT,
            user_id INTEGER REFERENCES users(id),
            verified_genre TEXT,
            mood TEXT,
            note TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS tracks (
            id INTEGER PRIMARY KEY,
            submission_id INTEGER UNIQUE REFERENCES submissions(id),
            title TEXT NOT NULL,
            artist TEXT NOT NULL,
            state TEXT NOT NULL,
            genre TEXT NOT NULL,
            mood TEXT NOT NULL,
            filename TEXT,
            mime TEXT,
            is_demo INTEGER NOT NULL DEFAULT 0,
            published INTEGER NOT NULL DEFAULT 1
        );
        CREATE TABLE IF NOT EXISTS playlists (
            id INTEGER PRIMARY KEY,
            name TEXT NOT NULL,
            category TEXT NOT NULL,
            description TEXT NOT NULL DEFAULT '',
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS playlist_tracks (
            playlist_id INTEGER NOT NULL REFERENCES playlists(id) ON DELETE CASCADE,
            track_id INTEGER NOT NULL REFERENCES tracks(id) ON DELETE CASCADE,
            position INTEGER NOT NULL DEFAULT 0,
            PRIMARY KEY (playlist_id, track_id)
        );
        CREATE TABLE IF NOT EXISTS inquiries (
            id INTEGER PRIMARY KEY,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            role TEXT NOT NULL,
            message TEXT NOT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS attempts (
            key TEXT NOT NULL,
            time INTEGER NOT NULL
        );
        CREATE TABLE IF NOT EXISTS account_reviews (
            id INTEGER PRIMARY KEY,
            user_id INTEGER NOT NULL REFERENCES users(id),
            admin_id INTEGER NOT NULL REFERENCES users(id),
            decision TEXT NOT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS review_logs (
            id INTEGER PRIMARY KEY,
            submission_id INTEGER NOT NULL REFERENCES submissions(id),
            admin_id INTEGER NOT NULL REFERENCES users(id),
            decision TEXT NOT NULL,
            verified_genre TEXT NOT NULL,
            mood TEXT NOT NULL,
            rights_checked INTEGER NOT NULL,
            quality_checked INTEGER NOT NULL,
            metadata_checked INTEGER NOT NULL,
            note TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
    ");

    $columns = $pdo->query('PRAGMA table_info(users)')->fetchAll();
    if (!in_array('account_status', array_column($columns, 'name'), true)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN account_status TEXT NOT NULL DEFAULT 'Approved'");
    }

    $count = (int) $pdo->query('SELECT COUNT(*) FROM tracks')->fetchColumn();
    if (!$count) {
        $q = $pdo->prepare('INSERT INTO tracks(title,artist,state,genre,mood,is_demo) VALUES(?,?,?,?,?,1)');
        $seedTracks = [
            ['Golden Hour', 'Amara K.', 'Lagos', 'Afrobeats', 'Upbeat'],
            ['Closer to Home', 'Kofi & The Band', 'FCT', 'Jazz', 'Relaxed'],
            ['Soft Life', 'The Blaq Notes', 'Rivers', 'Soul', 'Relaxed'],
            ['City Lights', 'Kola North', 'Kano', 'Afropop', 'Upbeat'],
            ['Easy Sunday', 'Ada June', 'Enugu', 'Soul', 'Relaxed'],
            ['The Long Way', 'Dara Blue', 'Oyo', 'Alté', 'Focused'],
        ];
        foreach ($seedTracks as $t) {
            $q->execute($t);
        }
    }

    $envAdminEmail = strtolower(trim((string) getenv('RADIANCE_ADMIN_EMAIL')));
    $envAdminPass = (string) getenv('RADIANCE_ADMIN_PASSWORD');
    if ($envAdminEmail && strlen($envAdminPass) >= 12) {
        $adminCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
        if ($adminCount === 0) {
            $q = $pdo->prepare("INSERT INTO users(name,email,password,role,account_status) VALUES(?,?,?,'admin','Approved')");
            $q->execute(['Radiance Admin', $envAdminEmail, password_hash($envAdminPass, PASSWORD_DEFAULT)]);
        }
    }

    return $pdo;
}

function e($s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function user(): ?array
{
    if (empty($_SESSION['uid'])) {
        return null;
    }
    $q = db()->prepare('SELECT id,name,email,role,category,state,mood,account_status FROM users WHERE id=?');
    $q->execute([$_SESSION['uid']]);
    return $q->fetch() ?: null;
}

function require_role(string $role): array
{
    $u = user();
    if (!$u || $u['role'] !== $role || $u['account_status'] !== 'Approved') {
        http_response_code(403);
        exit('Access denied. Please sign in with an approved ' . e($role) . ' account.');
    }
    return $u;
}

function token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function csrf(): void
{
    if (
        empty($_SESSION['csrf'])
        || !isset($_POST['csrf'])
        || !hash_equals($_SESSION['csrf'], (string) $_POST['csrf'])
    ) {
        http_response_code(403);
        exit('Your session expired. Refresh the page and try again.');
    }
}

function field(string $key, int $max = 150): string
{
    $v = trim((string) ($_POST[$key] ?? ''));
    return function_exists('mb_substr') ? mb_substr($v, 0, $max) : substr($v, 0, $max);
}

function flash(string $message, string $kind = 'success'): void
{
    $_SESSION['flash'] = [$kind, $message];
}

function go(string $page, string $tab = ''): never
{
    $url = 'index.php?p=' . urlencode($page);
    if ($tab !== '') {
        $url .= '&tab=' . urlencode($tab);
    }
    header('Location: ' . $url, true, 303);
    exit;
}

function attempt(string $prefix, int $max = 10, int $window = 900): void
{
    $key = hash('sha256', $prefix . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    $pdo = db();
    $pdo->prepare('DELETE FROM attempts WHERE time < ?')->execute([time() - 86400]);
    $q = $pdo->prepare('SELECT COUNT(*) FROM attempts WHERE key = ? AND time > ?');
    $q->execute([$key, time() - $window]);
    if ((int) $q->fetchColumn() >= $max) {
        throw new RuntimeException('Too many attempts. Please try again later.');
    }
    $pdo->prepare('INSERT INTO attempts(key,time) VALUES(?,?)')->execute([$key, time()]);
}

function valid_email(string $s): bool
{
    return (bool) filter_var($s, FILTER_VALIDATE_EMAIL) && strlen($s) <= 254;
}

function categories(): array
{
    return [
        'Café / coffee shop',
        'Restaurant',
        'Bar / lounge',
        'Gym / fitness',
        'Salon / spa',
        'Retail / boutique',
        'Hotel / hospitality',
        'Office / coworking',
        'Other',
    ];
}

function states(): array
{
    return [
        'Abia', 'Adamawa', 'Akwa Ibom', 'Anambra', 'Bauchi', 'Bayelsa', 'Benue', 'Borno',
        'Cross River', 'Delta', 'Ebonyi', 'Edo', 'Ekiti', 'Enugu', 'FCT', 'Gombe', 'Imo',
        'Jigawa', 'Kaduna', 'Kano', 'Katsina', 'Kebbi', 'Kogi', 'Kwara', 'Lagos', 'Nasarawa',
        'Niger', 'Ogun', 'Ondo', 'Osun', 'Oyo', 'Plateau', 'Rivers', 'Sokoto', 'Taraba',
        'Yobe', 'Zamfara',
    ];
}

function genres(): array
{
    return ['Afrobeats', 'Afropop', 'Highlife', 'Alté', 'Soul', 'Jazz', 'Gospel', 'Instrumental', 'Other'];
}

function moods(): array
{
    return ['Relaxed', 'Balanced', 'Upbeat', 'Focused'];
}

function select_options(array $list, string $selected = ''): string
{
    $s = '';
    foreach ($list as $v) {
        $s .= '<option value="' . e($v) . '"' . ($v === $selected ? ' selected' : '') . '>' . e($v) . '</option>';
    }
    return $s;
}
