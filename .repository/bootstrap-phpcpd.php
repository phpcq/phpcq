<?php

use Phpcq\Config\BuildConfigInterface;
use Phpcq\Plugin\ConfigurationPluginInterface;

return new class implements ConfigurationPluginInterface {
    public function getName() : string
    {
        return 'phpcpd';
    }

    /**
     * exclude         [array]  Exclude directories from code analysis (must be relative to source).
     * names           [array]  A list of file names to check [default: ["*.php"]].
     * names_exclude   [array]  A list of file names to exclude.
     * regexps_exclude [array]  A list of paths regexps to exclude (example: "#var/.*_tmp#")
     * log             [string] Write result in PMD-CPD XML format to file.
     * min_lines       [int]    Minimum number of identical lines [default: 5]
     * min_tokens      [int]    Minimum number of identical tokens [default: 70]
     * fuzzy           [bool]   Fuzz variable names
     *
     * custom_flags    [string] Any custom flags to pass to phpcpd. For valid flags refer to the phpcpd documentation.
     *
     * directories     [array]  source directories to be analyzed with phpcpd.
     *
     * @var string[]
     */
    private static $knownConfigKeys = [
        'exclude'         => 'exclude',
        'names'           => 'names',
        'names_exclude'   => 'names_exclude',
        'regexps_exclude' => 'regexps_exclude',
        'log'             => 'log',
        'min_lines'       => 'min_lines',
        'min_tokens'      => 'min_tokens',
        'fuzzy'           => 'fuzzy',
        'custom_flags'    => 'custom_flags',
        'directories'     => 'directories',
    ];

    public function validateConfig(array $config) : void
    {
        if ($diff = array_diff_key($config, self::$knownConfigKeys)) {
            throw new \Phpcq\Exception\RuntimeException(
                'Unknown config keys encountered: ' . implode(', ', array_keys($diff))
            );
        }
    }

    public function processConfig(array $config, BuildConfigInterface $buildConfig) : iterable
    {
        $args = [];
        if ([] !== ($excluded = (array) ($config['exclude'] ?? []))) {
            foreach ($excluded as $path) {
                if ('' === ($path = trim($path))) {
                    continue;
                }
                $args[] = '--exclude=' . $path;

            }
        }
        if ('' !== ($values = $this->commaValues($config, 'names'))) {
            $args[] = '--names=' . $values;
        }
        if ('' !== ($values = $this->commaValues($config, 'names_exclude'))) {
            $args[] = '--names-exclude=' . $values;
        }
        if ('' !== ($values = $this->commaValues($config, 'regexps_exclude'))) {
            $args[] = '--regexps-exclude=' . $values;
        }
        if ('' !== ($values = $config['log'] ?? '')) {
            $args[] = '--log-pmd=' . $values;
        }
        if ('' !== ($values = $config['min_lines'] ?? '')) {
            $args[] = '--min-lines=' . $values;
        }
        if ('' !== ($values = $config['min_tokens'] ?? '')) {
            $args[] = '--min-tokens=' . $values;
        }
        if ($config['fuzzy'] ?? false) {
            $args[] = '--fuzzy';
        }
        if ('' !== ($values = $config['custom_flags'] ?? '')) {
            $args[] = 'custom_flags';
        }

        yield $buildConfig
            ->getTaskFactory()
            ->buildRunPhar('phpcpd', array_merge($args, $config['directories']))
            ->withWorkingDirectory($buildConfig->getProjectConfiguration()->getProjectRootPath())
            ->build();
    }

    private function commaValues(array $config, string $key): string
    {
        if (!isset($config[$key])) {
            return '';
        }
        return implode(',', (array) $config[$key]);
    }
};
