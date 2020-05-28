<?php

declare(strict_types=1);

namespace Phpcq\Report;

use SimpleXMLElement;

final class Report
{
    /**
     * @psalm-var array<string,CheckstyleFile>
     * @var CheckstyleFile[]
     */
    private $files = [];

    public function checkstyle(string $fileName): CheckstyleFile
    {
        if (!isset($this->files[$fileName])) {
            $this->files[$fileName] = new CheckstyleFile($fileName);
        }

        return $this->files[$fileName];
    }

    public function asXml(?string $fileName = null): SimpleXMLElement
    {
        $xmlDocument = simplexml_load_string('<checkstyle></checkstyle>');

        foreach ($this->files as $file) {
            $file->appendToXml($xmlDocument);
        }

        if ($fileName) {
            $xmlDocument->asXML($fileName);
        }

        return $xmlDocument;
    }
}
