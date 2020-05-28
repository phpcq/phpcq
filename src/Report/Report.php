<?php

declare(strict_types=1);

namespace Phpcq\Report;

use SimpleXMLElement;

final class Report
{
    /**
     * @psalm-var array<string,File>
     * @var File[]
     */
    private $files = [];

    public function file(string $fileName): File
    {
        if (!isset($this->files[$fileName])) {
            $this->files[$fileName] = new File($fileName);
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
