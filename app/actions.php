<?php
declare(strict_types=1);

function handle_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    csrf();
    $action = field('action', 40);
    $allowed = [
        'setup-admin',
        'contact',
        'business-register',
        'business-profile',
        'login',
        'submit',
        'status',
        'claim',
        'review',
        'track',
        'playlist',
        'playlist-delete',
        'account-review',
        'change-password',
        'logout',
    ];
    if (!in_array($action, $allowed, true)) {
        http_response_code(400);
        exit('Invalid action');
    }

    $currentUser = user();
    $returns = [
        'setup-admin'       => ['admin-login', ''],
        'contact'           => ['contact', ''],
        'business-register' => ['business-register', ''],
        'business-profile'  => ['business-dashboard', 'settings'],
        'login'             => [field('role') === 'admin' ? 'admin-login' : 'login', ''],
        'submit'            => [
            $currentUser && $currentUser['role'] === 'artist' && $currentUser['account_status'] === 'Approved'
                ? 'artist-dashboard'
                : 'submit',
            $currentUser && $currentUser['role'] === 'artist' && $currentUser['account_status'] === 'Approved'
                ? 'submit'
                : '',
        ],
        'status'            => ['status', ''],
        'claim'             => ['claim', ''],
        'review'            => ['admin', 'reviews'],
        'track'             => ['admin', 'catalog'],
        'playlist'          => ['admin', 'playlists'],
        'playlist-delete'   => ['admin', 'playlists'],
        'account-review'    => ['admin', 'accounts'],
        'change-password'   => [
            $currentUser ? ($currentUser['role'] === 'admin' ? 'admin' : $currentUser['role'] . '-dashboard') : 'login',
            $currentUser ? ($currentUser['role'] === 'admin' ? 'audit' : 'settings') : '',
        ],
        'logout'            => ['home', ''],
    ];

    try {
        $pdo = db();
        switch ($action) {
            case 'setup-admin':
                if ((int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn() > 0) {
                    throw new RuntimeException('An administrator account already exists. Please sign in.');
                }
                $name = field('name');
                $email = strtolower(field('email', 254));
                $pass = (string) ($_POST['password'] ?? '');
                if (!$name || !valid_email($email) || strlen($pass) < 12 || strlen($pass) > 200) {
                    throw new RuntimeException('Enter your name, a valid email, and a password of at least 12 characters.');
                }
                $q = $pdo->prepare("INSERT INTO users(name,email,password,role,account_status) VALUES(?,?,?,'admin','Approved')");
                $q->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
                session_regenerate_id(true);
                $_SESSION['uid'] = (int) $pdo->lastInsertId();
                flash('Administrator account created. Welcome to Radiance Audio.');
                go('admin', 'overview');

            case 'contact':
                attempt('contact', 8, 3600);
                $name = field('name');
                $email = strtolower(field('email', 254));
                $role = field('role');
                $message = field('message', 3000);
                if (!$name || !valid_email($email) || !$message || !in_array($role, ['Business', 'Artist', 'Other'], true)) {
                    throw new RuntimeException('Please complete all fields with a valid email.');
                }
                $q = $pdo->prepare('INSERT INTO inquiries(name,email,role,message) VALUES(?,?,?,?)');
                $q->execute([$name, $email, $role, $message]);
                flash('Your message has been saved for our team. Email notifications are not configured.');
                go('contact');

            case 'business-register':
                if (user()) {
                    throw new RuntimeException('Sign out of your current account before registering another account.');
                }
                attempt('business-register', 8, 3600);
                $name = field('name');
                $email = strtolower(field('email', 254));
                $pass = (string) ($_POST['password'] ?? '');
                $category = field('category');
                $state = field('state');
                $mood = field('mood');
                if (
                    !$name
                    || !valid_email($email)
                    || strlen($pass) < 12
                    || strlen($pass) > 200
                    || !in_array($category, categories(), true)
                    || !in_array($state, states(), true)
                    || !in_array($mood, moods(), true)
                ) {
                    throw new RuntimeException('Complete all fields and use a password of at least 12 characters.');
                }
                $q = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email=?');
                $q->execute([$email]);
                if ($q->fetchColumn()) {
                    throw new RuntimeException('This email is already registered. Sign in instead.');
                }
                $q = $pdo->prepare("INSERT INTO users(name,email,password,role,category,state,mood,account_status) VALUES(?,?,?,?,?,?,?,'Pending')");
                $q->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), 'business', $category, $state, $mood]);
                flash('Your business account request is awaiting administrator approval.');
                go('account-pending');

            case 'business-profile':
                $u = require_role('business');
                $name = field('name');
                $category = field('category');
                $state = field('state');
                $mood = field('mood');
                if (
                    !$name
                    || !in_array($category, categories(), true)
                    || !in_array($state, states(), true)
                    || !in_array($mood, moods(), true)
                ) {
                    throw new RuntimeException('Please complete your business details.');
                }
                $pdo->prepare('UPDATE users SET name=?,category=?,state=?,mood=? WHERE id=?')
                    ->execute([$name, $category, $state, $mood, $u['id']]);
                flash('Business profile updated; recommendations have been refreshed.');
                go('business-dashboard', 'discover');

            case 'login':
                attempt('login', 12, 900);
                $email = strtolower(field('email', 254));
                $role = field('role');
                if (!in_array($role, ['business', 'artist', 'admin'], true)) {
                    throw new RuntimeException('Invalid sign-in request.');
                }
                $q = $pdo->prepare('SELECT * FROM users WHERE email=? AND role=?');
                $q->execute([$email, $role]);
                $u = $q->fetch();
                if (!$u || !password_verify((string) ($_POST['password'] ?? ''), $u['password'])) {
                    throw new RuntimeException('Email or password is incorrect.');
                }
                if ($u['account_status'] !== 'Approved') {
                    throw new RuntimeException(
                        $u['account_status'] === 'Pending'
                            ? 'Your account is awaiting administrator approval.'
                            : 'Your account is not approved. Please contact the team.'
                    );
                }
                session_regenerate_id(true);
                $_SESSION['uid'] = $u['id'];
                go($role === 'admin' ? 'admin' : $role . '-dashboard');

            case 'change-password':
                $current = user();
                if (!$current || $current['account_status'] !== 'Approved') {
                    throw new RuntimeException('Sign in with an approved account first.');
                }
                attempt('password-change', 10, 900);
                $q = $pdo->prepare('SELECT password FROM users WHERE id=?');
                $q->execute([$current['id']]);
                $old = (string) ($_POST['current_password'] ?? '');
                $new = (string) ($_POST['new_password'] ?? '');
                if (!password_verify($old, (string) $q->fetchColumn())) {
                    throw new RuntimeException('Current password is incorrect.');
                }
                if (strlen($new) < 12 || strlen($new) > 200) {
                    throw new RuntimeException('Use a new password of at least 12 characters.');
                }
                $pdo->prepare('UPDATE users SET password=? WHERE id=?')
                    ->execute([password_hash($new, PASSWORD_DEFAULT), $current['id']]);
                session_regenerate_id(true);
                flash('Password updated.');
                go(
                    $current['role'] === 'admin' ? 'admin' : $current['role'] . '-dashboard',
                    $current['role'] === 'admin' ? 'audit' : 'settings'
                );

            case 'logout':
                $_SESSION = [];
                session_regenerate_id(true);
                flash('You have signed out.');
                go('home');

            case 'submit':
                attempt('submit', 8, 3600);
                $current = user();
                if ($current && ($current['role'] !== 'artist' || $current['account_status'] !== 'Approved')) {
                    throw new RuntimeException('Sign in with an approved artist account or sign out before submitting.');
                }
                $artist = field('artist');
                $email = $current ? $current['email'] : strtolower(field('email', 254));
                $title = field('title');
                $state = field('state');
                $genre = field('genre');
                $description = field('description', 2000);
                if (
                    !$artist
                    || !$title
                    || !valid_email($email)
                    || !in_array($state, states(), true)
                    || !in_array($genre, genres(), true)
                    || empty($_POST['rights'])
                ) {
                    throw new RuntimeException('Complete all required fields and confirm your rights.');
                }
                $file = $_FILES['audio'] ?? null;
                if (!$file || $file['error'] !== UPLOAD_ERR_OK || $file['size'] < 128 || $file['size'] > 25 * 1024 * 1024) {
                    throw new RuntimeException('Upload a valid audio file between 128 bytes and 25 MB. Check your server upload limits.');
                }
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $mime = class_exists('finfo')
                    ? (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name'])
                    : (function_exists('mime_content_type') ? mime_content_type($file['tmp_name']) : '');
                $accept = [
                    'mp3' => ['audio/mpeg', 'audio/mp3'],
                    'wav' => ['audio/x-wav', 'audio/wav', 'audio/vnd.wave', 'audio/wave'],
                ];
                if (!isset($accept[$ext]) || !in_array($mime, $accept[$ext], true)) {
                    throw new RuntimeException('Only MP3 and WAV audio files are accepted. File type could not be verified.');
                }
                $filename = bin2hex(random_bytes(24)) . '.bin';
                if (!move_uploaded_file($file['tmp_name'], STORAGE . '/uploads/' . $filename)) {
                    throw new RuntimeException('Could not save the file. Check storage permissions.');
                }
                $code = $current ? null : bin2hex(random_bytes(24));
                try {
                    $q = $pdo->prepare('INSERT INTO submissions(artist_name,email,title,state,genre,description,filename,mime,size,token_hash,user_id) VALUES(?,?,?,?,?,?,?,?,?,?,?)');
                    $q->execute([
                        $artist,
                        $email,
                        $title,
                        $state,
                        $genre,
                        $description,
                        $filename,
                        $mime,
                        $file['size'],
                        $code ? hash('sha256', $code) : null,
                        $current['id'] ?? null,
                    ]);
                } catch (Throwable $e) {
                    @unlink(STORAGE . '/uploads/' . $filename);
                    throw $e;
                }
                if ($code) {
                    $_SESSION['receipt'] = ['email' => $email, 'code' => $code];
                    go('submitted');
                }
                flash('Your track has been submitted for review.');
                go('artist-dashboard', 'tracks');

            case 'status':
                attempt('status', 15, 900);
                $email = strtolower(field('email', 254));
                $hash = hash('sha256', field('code', 128));
                $q = $pdo->prepare('SELECT id,title,artist_name,status,note FROM submissions WHERE email=? AND token_hash=?');
                $q->execute([$email, $hash]);
                $s = $q->fetch();
                if (!$s) {
                    throw new RuntimeException('No matching submission found. Check your email and tracking code.');
                }
                $_SESSION['status_result'] = $s;
                go('status');

            case 'claim':
                if (user()) {
                    throw new RuntimeException('Sign out of your current account before creating another account.');
                }
                attempt('claim', 12, 900);
                $email = strtolower(field('email', 254));
                $code = field('code', 128);
                $pass = (string) ($_POST['password'] ?? '');
                if (strlen($pass) < 12 || strlen($pass) > 200) {
                    throw new RuntimeException('Use a password of at least 12 characters.');
                }
                $pdo->beginTransaction();
                $q = $pdo->prepare("SELECT * FROM submissions WHERE email=? AND token_hash=? AND status='Approved' AND user_id IS NULL");
                $q->execute([$email, hash('sha256', $code)]);
                $s = $q->fetch();
                if (!$s) {
                    $pdo->rollBack();
                    throw new RuntimeException('No approved unclaimed submission matched those details.');
                }
                $q = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email=?');
                $q->execute([$email]);
                if ($q->fetchColumn()) {
                    $pdo->rollBack();
                    throw new RuntimeException('An account already exists with this email. Sign in instead.');
                }
                $q = $pdo->prepare("INSERT INTO users(name,email,password,role,state,account_status) VALUES(?,?,?,'artist',?,'Pending')");
                $q->execute([$s['artist_name'], $email, password_hash($pass, PASSWORD_DEFAULT), $s['state']]);
                $id = (int) $pdo->lastInsertId();
                $q = $pdo->prepare('UPDATE submissions SET user_id=?,token_hash=NULL WHERE id=?');
                $q->execute([$id, $s['id']]);
                $pdo->prepare('UPDATE tracks SET published=0 WHERE submission_id=?')->execute([$s['id']]);
                $pdo->commit();
                flash('Your artist account request is awaiting administrator approval.');
                go('account-pending');

            case 'review':
                $reviewer = require_role('admin');
                $id = (int) ($_POST['id'] ?? 0);
                $status = field('status');
                $genre = field('genre');
                $mood = field('mood');
                $note = field('note', 1000);
                if (
                    !in_array($status, ['Approved', 'Needs changes', 'Rejected'], true)
                    || !in_array($genre, genres(), true)
                    || !in_array($mood, moods(), true)
                ) {
                    throw new RuntimeException('Choose a valid review outcome and classification.');
                }
                if (
                    $status === 'Approved'
                    && (empty($_POST['rights_check']) || empty($_POST['quality_check']) || empty($_POST['metadata_check']))
                ) {
                    throw new RuntimeException('Confirm rights, audio quality and metadata checks before approval.');
                }
                $q = $pdo->prepare('SELECT * FROM submissions WHERE id=?');
                $q->execute([$id]);
                $s = $q->fetch();
                if (!$s) {
                    throw new RuntimeException('Submission not found.');
                }
                $pdo->beginTransaction();
                $q = $pdo->prepare('UPDATE submissions SET status=?,verified_genre=?,mood=?,note=? WHERE id=?');
                $q->execute([$status, $genre, $mood, $note, $id]);
                if ($status === 'Approved') {
                    $canPublish = false;
                    if ($s['user_id']) {
                        $q = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id=? AND role='artist' AND account_status='Approved'");
                        $q->execute([$s['user_id']]);
                        $canPublish = (bool) $q->fetchColumn();
                    }
                    $q = $pdo->prepare('INSERT INTO tracks(submission_id,title,artist,state,genre,mood,filename,mime,is_demo,published) VALUES(?,?,?,?,?,?,?,?,0,?) ON CONFLICT(submission_id) DO UPDATE SET genre=excluded.genre,mood=excluded.mood,published=excluded.published');
                    $q->execute([$id, $s['title'], $s['artist_name'], $s['state'], $genre, $mood, $s['filename'], $s['mime'], (int) $canPublish]);
                } else {
                    $pdo->prepare('UPDATE tracks SET published=0 WHERE submission_id=?')->execute([$id]);
                }
                $q = $pdo->prepare('INSERT INTO review_logs(submission_id,admin_id,decision,verified_genre,mood,rights_checked,quality_checked,metadata_checked,note) VALUES(?,?,?,?,?,?,?,?,?)');
                $q->execute([
                    $id,
                    $reviewer['id'],
                    $status,
                    $genre,
                    $mood,
                    (int) !empty($_POST['rights_check']),
                    (int) !empty($_POST['quality_check']),
                    (int) !empty($_POST['metadata_check']),
                    $note,
                ]);
                $pdo->commit();
                flash('Review saved. Catalog visibility has been updated.');
                go('admin', 'reviews');

            case 'track':
                require_role('admin');
                $id = (int) ($_POST['id'] ?? 0);
                $genre = field('genre');
                $mood = field('mood');
                if (!in_array($genre, genres(), true) || !in_array($mood, moods(), true)) {
                    throw new RuntimeException('Invalid category.');
                }
                $q = $pdo->prepare('SELECT t.is_demo,s.status,u.account_status FROM tracks t LEFT JOIN submissions s ON s.id=t.submission_id LEFT JOIN users u ON u.id=s.user_id WHERE t.id=?');
                $q->execute([$id]);
                $track = $q->fetch();
                if (!$track) {
                    throw new RuntimeException('Track not found.');
                }
                if (
                    isset($_POST['published'])
                    && !(int) $track['is_demo']
                    && ($track['status'] !== 'Approved' || $track['account_status'] !== 'Approved')
                ) {
                    throw new RuntimeException('Only approved submissions from approved artist accounts can be made visible.');
                }
                $pdo->prepare('UPDATE tracks SET genre=?,mood=?,published=? WHERE id=?')
                    ->execute([$genre, $mood, isset($_POST['published']) ? 1 : 0, $id]);
                flash('Catalog track updated.');
                go('admin', 'catalog');

            case 'account-review':
                $admin = require_role('admin');
                $id = (int) ($_POST['id'] ?? 0);
                $status = field('account_status');
                if (!in_array($status, ['Approved', 'Rejected', 'Pending'], true)) {
                    throw new RuntimeException('Choose a valid account decision.');
                }
                $q = $pdo->prepare("SELECT id,role FROM users WHERE id=? AND role IN ('artist','business')");
                $q->execute([$id]);
                $account = $q->fetch();
                if (!$account) {
                    throw new RuntimeException('Account not found.');
                }
                $pdo->beginTransaction();
                $pdo->prepare('UPDATE users SET account_status=? WHERE id=?')->execute([$status, $id]);
                if ($account['role'] === 'artist') {
                    $q = $pdo->prepare("UPDATE tracks SET published=CASE WHEN ?='Approved' AND submission_id IN (SELECT id FROM submissions WHERE user_id=? AND status='Approved') THEN 1 ELSE 0 END WHERE submission_id IN (SELECT id FROM submissions WHERE user_id=?)");
                    $q->execute([$status, $id, $id]);
                }
                $pdo->prepare('INSERT INTO account_reviews(user_id,admin_id,decision) VALUES(?,?,?)')
                    ->execute([$id, $admin['id'], $status]);
                $pdo->commit();
                flash('Account decision saved.');
                go('admin', 'accounts');

            case 'playlist':
                require_role('admin');
                $id = (int) ($_POST['id'] ?? 0);
                $name = field('name');
                $category = field('category');
                $description = field('description', 500);
                $ids = array_values(array_unique(array_map('intval', $_POST['tracks'] ?? [])));
                if (!$name || !in_array($category, array_merge(['All businesses'], categories()), true)) {
                    throw new RuntimeException('Enter a playlist name and category.');
                }
                $pdo->beginTransaction();
                if ($id) {
                    $q = $pdo->prepare('UPDATE playlists SET name=?,category=?,description=? WHERE id=?');
                    $q->execute([$name, $category, $description, $id]);
                    $pdo->prepare('DELETE FROM playlist_tracks WHERE playlist_id=?')->execute([$id]);
                } else {
                    $q = $pdo->prepare('INSERT INTO playlists(name,category,description) VALUES(?,?,?)');
                    $q->execute([$name, $category, $description]);
                    $id = (int) $pdo->lastInsertId();
                }
                $q = $pdo->prepare('INSERT INTO playlist_tracks(playlist_id,track_id,position) SELECT ?,id,? FROM tracks WHERE id=?');
                foreach ($ids as $pos => $tid) {
                    $q->execute([$id, $pos, $tid]);
                }
                $pdo->commit();
                flash('Playlist saved.');
                go('admin', 'playlists');

            case 'playlist-delete':
                require_role('admin');
                $pdo->prepare('DELETE FROM playlists WHERE id=?')->execute([(int) ($_POST['id'] ?? 0)]);
                flash('Playlist removed.');
                go('admin', 'playlists');
        }
    } catch (Throwable $ex) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Radiance action error: ' . $ex->getMessage());
        flash(
            $ex instanceof RuntimeException ? $ex->getMessage() : 'Something went wrong. Please check the server log.',
            'error'
        );
        [$returnPage, $returnTab] = $returns[$action];
        go($returnPage, $returnTab);
    }
}
