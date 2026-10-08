<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

$root = dirname(__DIR__);
$action = $argv[1] ?? '';
if ($action === 'prepare') {
    $relative = 'build/staging/' . bin2hex(random_bytes(8)) . '/librett-player-registry';
    $stage = $root . '/' . $relative;
    if (!mkdir($stage, 0755, true)) {
        throw new RuntimeException('Cannot prepare package directory.');
    }
    $files = ['librett-player-registry.php', 'uninstall.php', 'composer.json', 'composer.lock', 'LICENSE', 'THIRD_PARTY_NOTICES.md', 'README.md', 'README-sr.md', 'AGENTS.md', 'CHANGELOG.md', 'CONTRIBUTING.md', 'CONTRIBUTING-sr.md', 'SECURITY.md', 'SECURITY-sr.md'];
    foreach (['src', 'languages', 'docs'] as $directory) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $directory, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->isLink() || !$file->isFile()) {
                throw new RuntimeException('Unexpected package source.');
            }
            $files[] = substr($file->getPathname(), strlen($root) + 1);
        }
    }
    foreach ($files as $file) {
        $destination = $stage . '/' . $file;
        if (!is_dir(dirname($destination)) && !mkdir(dirname($destination), 0755, true)) {
            throw new RuntimeException('Cannot create package subdirectory.');
        }
        if (!copy($root . '/' . $file, $destination)) {
            throw new RuntimeException('Cannot copy package source.');
        }
    }
    echo $relative, PHP_EOL;
} elseif ($action === 'zip') {
    $relative = $argv[2] ?? '';
    if (preg_match('~\Abuild/staging/[0-9a-f]{16}/librett-player-registry\z~', $relative) !== 1) {
        throw new RuntimeException('Invalid staging directory.');
    }
    $stage = $root . '/' . $relative;
    if (!is_file($stage . '/vendor/autoload.php')) {
        throw new RuntimeException('Runtime dependencies are missing.');
    }
    $installed = json_decode(file_get_contents($stage . '/vendor/composer/installed.json'), true, 512, JSON_THROW_ON_ERROR);
    if (($installed['dev'] ?? true) !== false) {
        throw new RuntimeException('Package includes development dependencies.');
    }
    require $stage . '/vendor/autoload.php';
    if (!class_exists(LibreTT\PlayerRegistry\Publication\Infrastructure\Json\SnapshotDecoder::class)) {
        throw new RuntimeException('Package autoload failed.');
    }
    $path = $root . '/build/librett-player-registry-development.zip';
    $zip = new ZipArchive();
    if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Cannot create ZIP.');
    }
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($stage, FilesystemIterator::SKIP_DOTS));
    $entries = [];
    foreach ($iterator as $file) {
        if ($file->isLink() || !$file->isFile()) {
            throw new RuntimeException('Unexpected package entry.');
        }
        $name = 'librett-player-registry/' . substr($file->getPathname(), strlen($stage) + 1);
        $entries[$name] = $file->getPathname();
    }
    ksort($entries);
    foreach ($entries as $name => $file) {
        if (!$zip->addFile($file, $name)) {
            throw new RuntimeException('Cannot add ZIP entry.');
        }
    }
    if (!$zip->close()) {
        throw new RuntimeException('Cannot finalize ZIP.');
    }
    file_put_contents($path . '.sha256', hash_file('sha256', $path) . '  ' . basename($path) . PHP_EOL);
    echo $path, PHP_EOL;
} else {
    throw new RuntimeException('Use prepare or zip with a prepared directory.');
}
