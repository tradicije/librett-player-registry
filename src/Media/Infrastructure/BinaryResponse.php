<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Media\Infrastructure;

final class BinaryResponse extends \WP_REST_Response
{
    public function __construct(public readonly string $bytes, string $mime, ?string $filename = null)
    {
        parent::__construct(null, 200);
        $this->header('Content-Type', $mime);
        $this->header('Content-Length', (string) strlen($bytes));
        $this->header('Cache-Control', 'private, no-store, max-age=0');
        $this->header('X-Content-Type-Options', 'nosniff');
        if ($filename !== null) {
            $this->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        }
    }
}
