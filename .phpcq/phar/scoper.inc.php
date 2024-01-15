<?php

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

$excludedFiles = (static function (): array {
    $files = [];
    // Symfony polyfills must live in global namespace.
    foreach (
        Finder::create()
            ->files()
            ->in(__DIR__ . '/../../vendor/symfony/polyfill-*')
            ->name('bootstrap.php') as $bootstrap
    ) {
        $files[] = $bootstrap->getPathName();
    }
    foreach (
        Finder::create()
            ->files()
            ->in(__DIR__ . '/../../vendor/symfony/polyfill-*/Resources/stubs')
            ->name('*.php') as $bootstrap
    ) {
        $files[] = $bootstrap->getPathName();
    }

    // Symfony deprecation contracts must live in global namespace.
    foreach (
        Finder::create()
            ->files()
            ->in(__DIR__ . '/../../vendor/symfony/deprecation-contracts')
            ->name('*.php') as $file
    ) {
        $files[] = $file->getPathName();
    }

    return $files;
})();

return [
    'exclude-namespaces' => [
        'Phpcq\PluginApi',
        'Symfony\Polyfill',
    ],
    'exclude-files' => $excludedFiles,
    'exclude-classes' => [
        'Gnupg'
    ],
];
