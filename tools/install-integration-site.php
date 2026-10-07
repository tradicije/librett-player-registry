<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

$root = getenv('LIBRETT_WP_ROOT');
$socket = getenv('LIBRETT_DB_SOCKET');
$dbName = getenv('LIBRETT_TEST_DB');
if (!$root || !is_file($root . '/wp-settings.php') || !$socket || !$dbName
    || preg_match('/\Alibrett_registry_test_[a-z0-9_]+\z/', $dbName) !== 1) {
    throw new RuntimeException('Set LIBRETT_WP_ROOT, LIBRETT_DB_SOCKET and a dedicated LIBRETT_TEST_DB.');
}
$connection = new mysqli('localhost', 'root', '', '', 0, $socket);
$connection->query('CREATE DATABASE IF NOT EXISTS `' . $dbName . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$connection->close();
$config = "<?php\ndefine('DB_NAME', " . var_export($dbName, true) . ");\n"
    . "define('DB_USER', 'root');\ndefine('DB_PASSWORD', '');\n"
    . "define('DB_HOST', " . var_export('localhost:' . $socket, true) . ");\n"
    . "define('DB_CHARSET', 'utf8mb4');\ndefine('DB_COLLATE', '');\n"
    . "define('WP_HOME', 'https://example.invalid');\ndefine('WP_SITEURL', 'https://example.invalid');\n"
    . "define('WP_HTTP_BLOCK_EXTERNAL', true);\ndefine('DISABLE_WP_CRON', true);\n"
    . "define('WP_AUTO_UPDATE_CORE', false);\ndefine('AUTOMATIC_UPDATER_DISABLED', true);\n"
    . "define('WP_DEBUG', true);\ndefine('WP_DEBUG_DISPLAY', false);\n"
    . "\$table_prefix = 'ltt_test_';\nif (!defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/'); }\nrequire ABSPATH . 'wp-settings.php';\n";
if (is_file($root . '/wp-config.php') && file_get_contents($root . '/wp-config.php') !== $config) {
    throw new RuntimeException('Refusing to overwrite a different WordPress configuration.');
}
file_put_contents($root . '/wp-config.php', $config);
define('WP_INSTALLING', true);
$_SERVER['HTTP_HOST'] = 'example.invalid';
$_SERVER['REQUEST_URI'] = '/';
require $root . '/wp-load.php';
add_filter('pre_wp_mail', static fn(): bool => true);
if (!is_blog_installed()) {
    require ABSPATH . 'wp-admin/includes/upgrade.php';
    wp_install('Synthetic integration site', 'registry-test-admin', 'test@example.invalid', false, '', bin2hex(random_bytes(24)), 'en_US');
}
echo 'Dedicated WordPress integration site ready; no mail or external HTTP enabled.', PHP_EOL;
