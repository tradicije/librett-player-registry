<?php

/**
 * Plugin Name: LibreTT Player Registry
 * Description: Development player/club registry with public profiles and one-way Desktop imports.
 * Author: Aleksa Dimitrijević
 * License: AGPL-3.0-or-later
 * Text Domain: librett-player-registry
 */
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$librettAutoload = __DIR__ . '/vendor/autoload.php';
if (PHP_VERSION_ID < 80500 || !is_file($librettAutoload)) {
    register_activation_hook(__FILE__, static function (): void {
        wp_die(esc_html__('LibreTT requires the PHP 8.5 development target and installed Composer dependencies.', 'librett-player-registry'));
    });
    add_action('admin_notices', static function (): void {
        echo '<div class="notice notice-error"><p>' . esc_html__('LibreTT bootstrap is unavailable. Check the development runtime and Composer dependencies.', 'librett-player-registry') . '</p></div>';
    });
    return;
}

require $librettAutoload;
\LibreTT\PlayerRegistry\Infrastructure\WordPress\Plugin::register(__FILE__);
