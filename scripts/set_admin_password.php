<?php
/**
 * Rotate the admin password.
 *
 * Usage:
 *   php scripts/set_admin_password.php "new-password"
 */

require_once __DIR__ . '/../Database.php';

$password = $argv[1] ?? '';

if (strlen($password) < 8) {
    fwrite(STDERR, "Usage: php scripts/set_admin_password.php <password min 8 chars>\n");
    exit(1);
}

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    fwrite(STDERR, "Could not connect to the database. Check the HOTEL_DB_* environment variables.\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $db->prepare("UPDATE utilisateurs SET password = ? WHERE username = 'admin'");
$stmt->execute([$hash]);

if ($stmt->rowCount() === 0) {
    fwrite(STDERR, "No 'admin' user found. Import database_schema.sql first.\n");
    exit(1);
}

echo "Admin password updated.\n";
