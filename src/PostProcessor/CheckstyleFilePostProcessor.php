<?php

declare(strict_types=1);

namespace Phpcq\PostProcessor;

use Phpcq\PluginApi\Version10\OutputInterface;
use Phpcq\Report\Report;

use function simplexml_load_file;

final class CheckstyleFilePostProcessor implements PostProcessorInterface
{
    /** @var string */
    private $fileName;

    /**
     * @var string
     */
    private $toolName;

    public function __construct(string $toolName, string $fileName)
    {
        $this->fileName = $fileName;
        $this->toolName = $toolName;
    }

    public function process(Report $report, array $consoleOutput, OutputInterface $output): void
    {
        $xml = simplexml_load_file($this->fileName);

        foreach ($xml->children() as $fileNode) {
            $file = $report->checkstyle($fileNode['name']->__toString());

            foreach ($fileNode->children() as $errorNode) {
                $file->add(
                    $errorNode['severity'] ? $errorNode['severity']->__toString() : 'error',
                    $errorNode['message'] ? $errorNode['message']->__toString() : '',
                    $this->toolName,
                    $errorNode['source'] ? $errorNode['source']->__toString() : null,
                    $errorNode['line'] ? (int) $errorNode['line']->__toString() : null,
                    $errorNode['column'] ? (int) $errorNode['column']->__toString() : null,
                );
            }
        }
    }
}
