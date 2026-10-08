<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Infrastructure\WordPress;

use LibreTT\PlayerRegistry\RegistryIdentity\Application\Actor;

final readonly class AdminGuard
{
    public function __construct(private SchemaGate $schema, private Preflight $preflight) {}

    public function actor(string $capability): Actor
    {
        if (!current_user_can($capability)) {
            wp_die(esc_html__('Permission denied.', 'librett-player-registry'), '', ['response' => 403]);
        }
        $this->preflight->assertReady();
        $this->schema->assertComplete();
        return new Actor(get_current_user_id(), [$capability]);
    }

    public function post(string $action): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            wp_die(esc_html__('POST required.', 'librett-player-registry'), '', ['response' => 405]);
        }
        check_admin_referer($action);
    }

    public static function form(string $action, bool $upload = false): void
    {
        echo '<form method="post"' . ($upload ? ' enctype="multipart/form-data"' : '') . ' action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="' . esc_attr($action) . '">';
        wp_nonce_field($action);
    }

    public static function hidden(string $name, string $value): void
    {
        echo '<input type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '">';
    }

    public static function field(string $name, string $label, string $value = '', int $maximum = 1000): void
    {
        echo '<p><label>' . esc_html($label) . '<br><input class="large-text" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '" maxlength="' . esc_attr((string) $maximum) . '"></label></p>';
    }
}
