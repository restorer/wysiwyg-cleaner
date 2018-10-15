<?php

namespace WysiwygCleaner\Clean;

use WysiwygCleaner\Html\HtmlNode;
use WysiwygCleaner\Html\HtmlText;
use WysiwygCleaner\Html\HtmlElement;
use WysiwygCleaner\Html\HtmlContainer;
use WysiwygCleaner\Html\HtmlDocument;
use WysiwygCleaner\CleanerException;
use WysiwygCleaner\TypeUtils;

class Flattener
{
    private $flattenTags;
    private $keepAttributes;

    public function __construct(array $flattenTags, array $keepAttributes)
    {
        $this->flattenTags = array_map('\strtolower', $flattenTags);
        $this->keepAttributes = array_map('\strtolower', $keepAttributes);
    }

    public function flatten(HtmlDocument $document) : HtmlDocument
    {
        $result = new HtmlDocument();

        foreach ($document->getChildren() as $child) {
            $this->flattenNode($result, $child);
        }

        return $result;
    }

    private function flattenNode(HtmlContainer $into, HtmlNode $node)
    {
        if ($node instanceof HtmlElement) {
            $cleanedAttributes = [];

            foreach ($node->getAttributes() as $name => $value) {
                if (in_array($name, $this->keepAttributes, true)) {
                    $cleanedAttributes[$name] = $value;
                }
            }

            if (!in_array($node->getTag(), $this->flattenTags, true) || !empty($cleanedAttributes)) {
                $cleanedNode = new HtmlElement($node->getTag(), $cleanedAttributes);
                $cleanedNode->setComputedStyle($node->getComputedStyle());

                $into->appendChild($cleanedNode);
                $into = $cleanedNode;
            }

            foreach ($node->getChildren() as $child) {
                $this->flattenNode($into, $child);
            }
        } elseif ($node instanceof HtmlText) {
            $into->appendChild($node);
        } else {
            throw new ParserException('Doesn\'t know what to do with node "' . TypeUtils::getClass($node) . '"');
        }
    }
}
