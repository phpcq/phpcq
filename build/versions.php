<?php

declare(strict_types=1);

(new class(... $GLOBALS['argv']) {
    private string $versionsJson;
    private string $composerJson;
    private string $version;
    private string $phar;
    private ?string $signature;

    public function __construct(
        string $script,
        string $versionsJson,
        string $composerJson,
        string $version,
        string $phar,
        ?string $signature = null
    ) {
        $versionChunks = explode(' ', $version);

        $this->versionsJson = $this->resolvePath($versionsJson);
        $this->composerJson = $this->resolvePath($composerJson);
        $this->version      = array_pop($versionChunks);
        $this->phar         = $phar;
        $this->signature    = $signature;
    }
    public function __invoke(): void
    {
        try {
            $versions               = $this->readVersions();
            $versions['versions'][] = $this->createVersion();

            $this->saveVersions($versions);
        } catch (Throwable $exception) {
            echo $exception->getMessage();

            exit(1);
        }

        exit(0);
    }

    private function resolvePath(string $path): string
    {
        if (strpos($path, '.') === 0) {
            return ((string) getcwd()) . '/' . $path;
        }

        return $path;
    }

    private function readVersions(): array
    {
        if (! file_exists($this->versionsJson)) {
            return [
                'updated' => date(DATE_ATOM),
                'versions' => [],
            ];
        }

        $json = json_decode((string) file_get_contents($this->versionsJson), true, 512, JSON_THROW_ON_ERROR);

        // Filter out versions with the same phar file which indicates what the file is overridden.
        $json['versions'] = array_values(
            array_filter(
                $json['versions'],
                fn (array $version): bool => $version['phar'] !== $this->phar,
            )
        );

        return $json;
    }

    /** @return array{version: string, phar: string, signature: string|null, requirements: array{non-empty-string, non-empty-string}} */
    private function createVersion(): array
    {
        $requirements = $this->readRequirements();

        return [
            'version'      => $this->version,
            'phar'         => $this->phar,
            'signature'    => $this->signature,
            'requirements' => $requirements,
        ];
    }

    private function saveVersions(array $versions): void
    {
        $versions['updated'] = date(DATE_ATOM);

        file_put_contents($this->versionsJson, json_encode($versions, JSON_THROW_ON_ERROR));
    }

    private function readRequirements(): array
    {
        if (! file_exists($this->composerJson)) {
            throw new RuntimeException('Could not find composer.json');
        }

        $composerJson = json_decode(file_get_contents($this->composerJson), true, 512, JSON_THROW_ON_ERROR);
        $requirements = [];

        foreach ($composerJson['require'] as $name => $version) {
            // Recognize all plattform dependencies, see
            // https://getcomposer.org/doc/articles/composer-platform-dependencies.md#different-types-of-platform-packages
            if (! preg_match('/^(php(-.+)?|ext-.+|lib-.+|composer(-(?:plugin|runtime)-api)?)$/', $name)) {
                continue;
            }

            $requirements[$name] = $version;
        }

        return $requirements;
    }
})();
