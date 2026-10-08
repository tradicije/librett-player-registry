<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

set_exception_handler(static function (Throwable $failure): never {
    fwrite(STDERR, $failure::class . ': ' . $failure->getMessage() . PHP_EOL . $failure->getTraceAsString() . PHP_EOL);
    exit(1);
});
register_shutdown_function(static function (): void {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        fwrite(STDERR, $error['message'] . ' at ' . $error['file'] . ':' . $error['line'] . PHP_EOL);
    }
});
$project = dirname(__DIR__);
$mode = $argv[1] ?? '';
if ($mode === '--bootstrap') {
    $directory = $argv[2] ?? '';
    if (preg_match('~\A' . preg_quote($project, '~') . '/local/dev/package-check-[0-9a-f]{8}/wordpress\z~', $directory) !== 1) {
        throw new RuntimeException('Invalid verification site.');
    }
    $_SERVER['HTTP_HOST'] = 'example.invalid';
    $_SERVER['REQUEST_URI'] = '/';
    require $directory . '/wp-load.php';
    if (!str_starts_with(DB_NAME, 'librett_registry_test_package_') || !WP_HTTP_BLOCK_EXTERNAL || !DISABLE_WP_CRON) {
        throw new RuntimeException('Unsafe verification environment.');
    }
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    if (!is_plugin_active('librett-player-registry/librett-player-registry.php') || !shortcode_exists('librett_registry') || !has_action('admin_post_librett_registry_import')) {
        throw new RuntimeException('Packaged plugin did not bootstrap its adapters.');
    }
    $routes = rest_get_server()->get_routes();
    if (!isset($routes['/librett-registry/v1/snapshot'])) {
        throw new RuntimeException('Packaged public API was not registered.');
    }
    $db = new LibreTT\PlayerRegistry\Infrastructure\WordPress\Database($wpdb);
    $registry = new LibreTT\PlayerRegistry\RegistryIdentity\Infrastructure\WordPressRegistryRepository($db);
    if ($registry->current() !== null) {
        throw new RuntimeException('Activation unexpectedly created a registry identity.');
    }
    $user = get_user_by('login', 'registry-test-admin');
    wp_set_current_user($user->ID);
    (new LibreTT\PlayerRegistry\RegistryIdentity\Application\CreateRegistry($registry, $db, new LibreTT\PlayerRegistry\RegistryIdentity\Infrastructure\RamseyUuidGenerator()))->execute(new LibreTT\PlayerRegistry\RegistryIdentity\Application\Actor($user->ID, ['librett_registry_manage_settings']), 'Synthetic package registry');
    if ($registry->current() === null) {
        throw new RuntimeException('Packaged registry setup failed.');
    }
    echo 'Extracted ZIP: second-request bootstrap, admin hooks, shortcode/API and primary setup passed.', PHP_EOL;
    exit;
}
$wordpress = getenv('LIBRETT_WP_ROOT');
if (!$wordpress || !is_file($wordpress . '/wp-settings.php') || !str_starts_with(getenv('LIBRETT_TEST_DB') ?: '', 'librett_registry_test_')) {
    throw new RuntimeException('Requires the isolated development WordPress environment.');
}
$token = bin2hex(random_bytes(4));
$directory = $project . '/local/dev/package-check-' . $token . '/wordpress';
mkdir($directory . '/wp-content/plugins', 0755, true);
foreach (glob($wordpress . '/*.php') as $file) {
    if (basename($file) !== 'wp-config.php') {
        copy($file, $directory . '/' . basename($file));
    }
}
foreach (['wp-admin', 'wp-includes'] as $name) {
    if (!symlink($wordpress . '/' . $name, $directory . '/' . $name)) {
        throw new RuntimeException('Cannot link WordPress core.');
    }
}
$zip = new ZipArchive();
if ($zip->open($project . '/build/librett-player-registry-development.zip') !== true) {
    throw new RuntimeException('Cannot open plugin ZIP.');
}
for ($index = 0; $index < $zip->numFiles; ++$index) {
    $name = $zip->getNameIndex($index);
    if (!is_string($name) || !str_starts_with($name, 'librett-player-registry/') || str_contains($name, '..') || str_contains($name, '\\')) {
        throw new RuntimeException('Unsafe ZIP entry.');
    }
    if (preg_match('~\Alibrett-player-registry/(?:tests|tools|local|\.git)/~', $name) === 1 || str_starts_with($name, 'librett-player-registry/vendor/phpunit/')) {
        throw new RuntimeException('ZIP includes development/local material.');
    }
}
if (!$zip->extractTo($directory . '/wp-content/plugins')) {
    throw new RuntimeException('Cannot extract plugin ZIP.');
}
$zip->close();
putenv('LIBRETT_WP_ROOT=' . $directory);
putenv('LIBRETT_TEST_DB=librett_registry_test_package_' . $token);
require $project . '/tools/install-integration-site.php';
$user = get_user_by('login', 'registry-test-admin');
wp_set_current_user($user->ID);
require_once ABSPATH . 'wp-admin/includes/plugin.php';
$result = activate_plugin('librett-player-registry/librett-player-registry.php');
if (is_wp_error($result)) {
    throw new RuntimeException('Packaged plugin activation failed: ' . $result->get_error_message());
}
// Capabilities granted during activation become visible on the next user load.
wp_set_current_user(0);
wp_set_current_user($user->ID);
$tables = $wpdb->get_col($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($wpdb->prefix . 'librett_registry_') . '%'));
if (count($tables) !== 22 || !current_user_can('librett_registry_import') || !current_user_can('librett_registry_publish_profiles')) {
    throw new RuntimeException('Packaged activation schema/capabilities failed: tables=' . count($tables) . ', import=' . (int) current_user_can('librett_registry_import') . ', publish=' . (int) current_user_can('librett_registry_publish_profiles'));
}
echo 'Extracted ZIP activation: 22 empty custom tables and administrator capabilities passed on PHP ', PHP_VERSION, '.', PHP_EOL;
$process = proc_open([PHP_BINARY, __FILE__, '--bootstrap', $directory], [0 => STDIN, 1 => STDOUT, 2 => STDERR], $pipes);
if (!is_resource($process) || proc_close($process) !== 0) {
    throw new RuntimeException('Packaged second-request verification failed.');
}
