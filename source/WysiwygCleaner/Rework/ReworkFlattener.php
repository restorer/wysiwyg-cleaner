<?php

namespace WysiwygCleaner\Rework;

use WysiwygCleaner\CleanerException;
use WysiwygCleaner\Html\HtmlContainer;
use WysiwygCleaner\Html\HtmlDocument;
use WysiwygCleaner\Html\HtmlElement;
use WysiwygCleaner\Html\HtmlNode;
use WysiwygCleaner\Html\HtmlText;
use WysiwygCleaner\TypeUtils;

class ReworkFlattener
{
    private $flattenTags;
    private $keepAttributes;

    public function __construct(array $flattenTags, array $keepAttributes)
    {
        $this->flattenTags = array_map('\strtolower', $flattenTags);
        $this->keepAttributes = array_map('\strtolower', $keepAttributes);
    }

    public function flatten(HtmlDocument $document)
    {
        $children = $document->getChildren();
        $document->setChildren([]);

        foreach ($children as $child) {
            $this->flattenNode($document, $child);
        }
    }

    private function flattenNode(HtmlContainer $destination, HtmlNode $node)
    {
        if ($node instanceof HtmlElement) {
            $cleanedAttributes = [];

            foreach ($node->getAttributes() as $name => $value) {
                if (in_array($name, $this->keepAttributes, true)) {
                    $cleanedAttributes[$name] = $value;
                }
            }

            // TODO: this will fail on, for example, <span style="display: block;">...</span>
            if (!in_array($node->getTag(), $this->flattenTags, true) || !empty($cleanedAttributes)) {
                $cleanedNode = new HtmlElement($node->getTag(), $cleanedAttributes);
                $cleanedNode->setComputedStyle($node->getComputedStyle());

                $destination->appendChild($cleanedNode);
                $destination = $cleanedNode;
            }

            foreach ($node->getChildren() as $child) {
                $this->flattenNode($destination, $child);
            }
        } elseif ($node instanceof HtmlText) {
            $destination->appendChild($node);
        } else {
            throw new CleanerException('Doesn\'t know what to do with node "' . TypeUtils::getClass($node) . '"');
        }
    }
}
