<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()->in([__DIR__ . '/src', __DIR__ . '/tests', __DIR__ . '/tools'])->append([__DIR__ . '/librett-player-registry.php', __DIR__ . '/uninstall.php', __FILE__]);
return (new PhpCsFixer\Config())->setRules(['@PER-CS3x0' => true])->setFinder($finder);
