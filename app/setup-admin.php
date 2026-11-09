<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    exit("Run this script from the command line.\n");
}

if ((int) db()->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn()) {
    exit("An admin already exists. Sign in at public/admin.php.\n");
}

echo "Create the first admin. Use a unique password of at least 12 characters. Input will be visible.\n";
echo "Name: ";
$name = trim((string) fgets(STDIN));
echo "Email: ";
$email = strtolower(trim((string) fgets(STDIN)));
echo "Password: ";
$password = trim((string) fgets(STDIN));

if (!$name || !valid_email($email) || strlen($password) < 12) {
    exit("Invalid input (ensure a valid email and a password of at least 12 characters). Run again.\n");
}

$q = db()->prepare("INSERT INTO users(name,email,password,role,account_status) VALUES(?,?,?,'admin','Approved')");
$q->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
echo "Admin created. Sign in at public/admin.php.\n";
