<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Publication\Infrastructure\Json;

use JsonStreamingParser\Listener\IdleListener;
use LibreTT\PlayerRegistry\RegistryIdentity\Application\RegistryFailure;

final class BoundedListener extends IdleListener
{
    /** @var list<array{array:bool,keys:array<string,true>}> */
    private array $frames = [];
    private int $items = 0;
    public bool $finished = false;

    public function startObject(): void
    {
        $this->start(false);
    }

    public function startArray(): void
    {
        $this->start(true);
    }

    private function start(bool $array): void
    {
        $this->item();
        if (count($this->frames) >= 8) {
            throw new RegistryFailure('resource_limit');
        }
        $this->frames[] = ['array' => $array, 'keys' => []];
    }

    private function item(): void
    {
        if ($this->frames !== [] && $this->frames[array_key_last($this->frames)]['array'] && ++$this->items > 100000) {
            throw new RegistryFailure('resource_limit');
        }
    }

    public function endObject(): void
    {
        array_pop($this->frames);
    }

    public function endArray(): void
    {
        array_pop($this->frames);
    }

    public function key(string $key): void
    {
        $index = array_key_last($this->frames);
        if ($index === null || count($this->frames[$index]['keys']) >= 32 || strlen($key) > 32768 || isset($this->frames[$index]['keys']['k:' . $key])) {
            throw new RegistryFailure('invalid_json');
        }
        $this->frames[$index]['keys']['k:' . $key] = true;
    }

    public function value(mixed $value): void
    {
        $this->item();
        if (is_string($value) && strlen($value) > 32768) {
            throw new RegistryFailure('resource_limit');
        }
    }

    public function endDocument(): void
    {
        $this->finished = true;
    }
}
