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
    /** @var array[] */
    private $tagsRules;

    /** @var array[] */
    private $attributesRules;

    /** @var array[] */
    private $classesRules;

    /**
     * @param array[] $tagsRules
     * @param array[] $classesRules
     * @param array[] $attributesRules
     */
    public function __construct(array $tagsRules, array $classesRules, array $attributesRules)
    {
        $this->tagsRules = $tagsRules;
        $this->classesRules = $classesRules;
        $this->attributesRules = $attributesRules;
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
                if ($name === HtmlElement::ATTR_CLASS) {
                    $classNameList = \array_values(
                        \array_filter(
                            explode(' ', $value),
                            function (string $className) : bool {
                                return ($className !== ''
                                    && CleanerUtils::matchRules($this->classesRules, [$className])
                                );
                            }
                        )
                    );

                    if (!empty($classNameList)) {
                        $cleanedAttributes[$name] = implode(' ', $classNameList);
                    }
                } elseif (CleanerUtils::matchRules($this->attributesRules, [$name, $value])) {
                    $cleanedAttributes[$name] = $value;
                }
            }

            if (!empty($cleanedAttributes)
                || CleanerUtils::matchRules(
                    $this->tagsRules,
                    [$node->getTag(), 'inline' => $node->getComputedStyle()->isInlineDisplay()]
                )
            ) {
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
}
