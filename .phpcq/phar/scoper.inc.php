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

    return $files;
})();

return [
    'whitelist' => [
        '\Phpcq\PluginApi\*'
    ],
    'files-whitelist' => $symfonyPolyfill,
];
