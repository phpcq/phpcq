<?php

declare(strict_types=1);

namespace Phpcq\Runner\Report\Writer;

use DOMDocument;
use DOMElement;
use DOMText;
use Symfony\Component\Filesystem\Filesystem;

final class XmlBuilder
{
    /**
     * @var DOMDocument
     */
    protected $document;

    public function __construct(protected string $targetPath, string $rootNode, private readonly ?string $rootNameSpace)
    {
        $this->document      = new DOMDocument('1.0');
        $this->document->appendChild($this->createElement($rootNode));
    }

    public function write(string $fileName): void
    {
        $filesystem = new Filesystem();
        $filesystem->mkdir($this->targetPath);

        $this->document->formatOutput = true;
        $this->document->preserveWhiteSpace = false;
        $this->document->save($this->targetPath . '/' . $fileName);
    }

    public function getDocumentElement(): DOMElement
    {
        return $this->document->documentElement;
    }

    public function createElement(string $name, ?DOMElement $parent = null): DOMElement
    {
        if (null !== $this->rootNameSpace) {
            $element = $this->document->createElementNS($this->rootNameSpace, $name);
        } else {
            $element = $this->document->createElement($name);
        }

        if (null !== $parent) {
            $parent->appendChild($element);
        }

        return $element;
    }

    public function setAttribute(DOMElement $element, string $name, string $value): void
    {
        if ((null !== $this->rootNameSpace) && ($element->namespaceURI !== $this->rootNameSpace)) {
            $element->setAttributeNS($this->rootNameSpace, $name, $value);
            return;
        }

        $element->setAttribute($name, $value);
    }

    public function getAttribute(DOMElement $element, string $name): ?string
    {
        if ((null !== $this->rootNameSpace) && ($element->namespaceURI !== $this->rootNameSpace)) {
            return $element->hasAttributeNS($this->rootNameSpace, $name)
                ? $element->getAttributeNS($this->rootNameSpace, $name)
                : null;
        }

        return $element->hasAttribute($name)
            ? $element->getAttribute($name)
            : null;
    }

    public function setTextContent(DOMElement $element, string $value): void
    {
        if ('' === $value) {
            return;
        }
        $element->appendChild(new DOMText($value));
    }
}
