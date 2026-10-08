<?php

/**
 * Plugin Name: LibreTT Player Registry
 * Description: Development player/club registry with public profiles and one-way Desktop imports.
 * Requires at least: 7.1.3
 * Requires PHP: 8.3.3
 * Author: Aleksa Dimitrijević
 * License: AGPL-3.0-or-later
 * Text Domain: librett-player-registry
 */
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$librettAutoload = __DIR__ . '/vendor/autoload.php';
$librettBootstrapError = null;
if (PHP_VERSION_ID < 80303 || PHP_VERSION_ID >= 80600 || PHP_INT_SIZE !== 8) {
    $librettBootstrapError = sprintf(
        __('LibreTT requires 64-bit PHP 8.3.3–8.5.x. This server runs PHP %s.', 'librett-player-registry'),
        PHP_VERSION,
    );
} elseif (!is_file($librettAutoload)) {
    $librettBootstrapError = __('LibreTT dependencies are missing. Install the prepared plugin ZIP containing vendor/autoload.php; a GitHub source ZIP is incomplete.', 'librett-player-registry');
}
if ($librettBootstrapError !== null) {
    register_activation_hook(__FILE__, static function () use ($librettBootstrapError): void {
        wp_die(esc_html($librettBootstrapError));
    });
    add_action('admin_notices', static function () use ($librettBootstrapError): void {
        echo '<div class="notice notice-error"><p>' . esc_html($librettBootstrapError) . '</p></div>';
    });
    return;
}

require $librettAutoload;
\LibreTT\PlayerRegistry\Infrastructure\WordPress\Plugin::register(__FILE__);
