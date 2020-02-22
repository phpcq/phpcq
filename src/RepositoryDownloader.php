<?php

declare(strict_types=1);

namespace Phpcq;


final class RepositoryDownloader
{
    /** @var string */
    private $targetDir;

    /**
     * RepositoryDownloader constructor.
     *
     * @param string $targetDir
     */
    public function __construct(string $targetDir)
    {
        $this->targetDir = $targetDir;
    }

    /**
     * @param string|array $repository
     */
    public function download($repository): void
    {

    }
}
