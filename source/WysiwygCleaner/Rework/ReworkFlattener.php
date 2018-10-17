<?php

namespace WysiwygCleaner\Rework;

use WysiwygCleaner\CleanerException;
use WysiwygCleaner\CleanerUtils;
use WysiwygCleaner\Html\HtmlContainer;
use WysiwygCleaner\Html\HtmlDocument;
use WysiwygCleaner\Html\HtmlElement;
use WysiwygCleaner\Html\HtmlNode;
use WysiwygCleaner\Html\HtmlText;

class ReworkFlattener
{
    /** @var string[] */
    private $flattenInlineTags;

    /** @var string[] */
    private $keepAttributes;

    /**
     * @param array $flattenInlineTags
     * @param array $keepAttributes
     */
    public function __construct(array $flattenInlineTags, array $keepAttributes)
    {
        $this->flattenInlineTags = array_map('\strtolower', $flattenInlineTags);
        $this->keepAttributes = array_map('\strtolower', $keepAttributes);
    }

    /**
     * @param HtmlDocument $document
     *
     * @throws CleanerException
     */
    public function flatten(HtmlDocument $document)
    {
        $children = $document->getChildren();
        $document->setChildren([]);

        foreach ($children as $child) {
            $this->flattenNode($document, $child);
        }
    }

    /**
     * @param HtmlContainer $destination
     * @param HtmlNode $node
     *
     * @throws CleanerException
     */
    private function flattenNode(HtmlContainer $destination, HtmlNode $node)
    {
        if ($node instanceof HtmlElement) {
            $cleanedAttributes = [];

            foreach ($node->getAttributes() as $name => $value) {
                if (\in_array($name, $this->keepAttributes, true)) {
                    $cleanedAttributes[$name] = $value;
                }
            }

            if (!empty($cleanedAttributes) || !$this->canFlattenInlineElement($node)) {
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
            throw new CleanerException('Doesn\'t know what to do with node "' . CleanerUtils::getClass($node) . '"');
        }
    }

    /**
     * @param HtmlElement $element
     *
     * @return bool
     */
    private function canFlattenInlineElement(HtmlElement $element) : bool
    {
        return \in_array($element->getTag(), $this->flattenInlineTags, true)
            && CleanerUtils::isInlineDisplay($element->getComputedStyle()->getDisplay());
    }
}
