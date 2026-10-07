<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';
$root = getenv('LIBRETT_WP_ROOT');
if (!$root || !is_file($root . '/wp-config.php')) {
    throw new RuntimeException('Configure a disposable WordPress site first; see DEVELOPMENT.md.');
}
$_SERVER['HTTP_HOST'] = 'example.invalid';
$_SERVER['REQUEST_URI'] = '/';
require $root . '/wp-load.php';
if (!str_starts_with(DB_NAME, 'librett_registry_test_') || !WP_HTTP_BLOCK_EXTERNAL || !DISABLE_WP_CRON) {
    throw new RuntimeException('Integration tests require an isolated test database and disabled external services.');
}
add_filter('pre_wp_mail', static fn(): bool => true);
require_once ABSPATH . 'wp-admin/includes/plugin.php';
