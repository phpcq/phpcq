<?php

declare(strict_types=1);

namespace Phpcq\PostProcessor;

use DOMDocument;
use DOMElement;
use DOMNode;
use Phpcq\PluginApi\Version10\OutputInterface;
use Phpcq\PluginApi\Version10\PostProcessorInterface;
use Phpcq\PluginApi\Version10\ReportInterface;
use Phpcq\Report\Report;

use function strlen;
use function strpos;
use function substr;

final class CheckstyleFilePostProcessor implements PostProcessorInterface
{
    /**
     * @var string
     */
    private $toolName;

    /**
     * @var string
     */
    private $fileName;

    /**
     * @var string
     */
    private $rootDir;

    public function __construct(string $toolName, string $fileName, string $rootDir)
    {
        $this->fileName = $fileName;
        $this->toolName = $toolName;
        $this->rootDir  = $rootDir;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @psalm-suppress PossiblyNullArgument
     */
    public function process(ReportInterface $report, array $consoleOutput, int $exitCode, OutputInterface $output): void
    {
        $xmlDocument = new DOMDocument('1.0');
        $xmlDocument->load($this->fileName);
        $rootNode = $xmlDocument->firstChild;

        $report->addToolReport($this->toolName, $exitCode === 0 ? Report::STATUS_PASSED : Report::STATUS_FAILED);

        if (!$rootNode instanceof DOMNode) {
            return;
        }

        foreach ($rootNode->childNodes as $childNode) {
            if (!$childNode instanceof DOMElement) {
                continue;
            }

            $fileName = $childNode->getAttribute('name');
            if (strpos($fileName, $this->rootDir) === 0) {
                $fileName = substr($fileName, strlen($this->rootDir) + 1);
            }

            $file = $report->addCheckstyle($fileName);

            foreach ($childNode->childNodes as $errorNode) {
                if (!$errorNode instanceof DOMElement) {
                    continue;
                }

                $file->add(
                    $this->getXmlAttribute($errorNode, 'severity', 'error'),
                    $this->getXmlAttribute($errorNode, 'message', ''),
                    $this->toolName,
                    $this->getXmlAttribute($errorNode, 'source'),
                    $this->getIntXmlAttribute($errorNode, 'line'),
                    $this->getIntXmlAttribute($errorNode, 'column'),
                );
            }
        }
    }

    /**
     * @param mixed $defaultValue
     */
    private function getXmlAttribute(DOMElement $element, string $attribute, ?string $defaultValue = null): ?string
    {
        if ($element->hasAttribute($attribute)) {
            return $element->getAttribute($attribute);
        }

        return $defaultValue;
    }

    private function getIntXmlAttribute(DOMElement $element, string $attribute): ?int
    {
        $value = $this->getXmlAttribute($element, $attribute);
        if ($value === null) {
            return null;
        }

        return (int) $value;
    }
}
