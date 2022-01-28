<?php

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

// Symfony polyfills must live in global namespace.
$symfonyPolyfill = (static function (): array {
    $files = [];
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

    return $files;
})();

return [
    'whitelist' => [
        '\Phpcq\PluginApi\*',
        '\Symfony\Polyfill\*',
    ],
    'files-whitelist' => $symfonyPolyfill,
    'patchers' => [
        static function (string $filePath, string $prefix, string $content): string {
            if ($filePath === 'vendor/phpcq/gnupg/src/GnuPGFactory.php') {
                return str_replace('use ' . $prefix . '\Gnupg;', 'use Gnupg;', $content);
            }

            return $content;
        }
    ]
];
