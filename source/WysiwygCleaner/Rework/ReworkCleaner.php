<?php

namespace WysiwygCleaner\Rework;

use WysiwygCleaner\CleanerException;
use WysiwygCleaner\CleanerUtils;
use WysiwygCleaner\Css\CssDeclaration;
use WysiwygCleaner\Css\CssStyle;
use WysiwygCleaner\Html\HtmlContainer;
use WysiwygCleaner\Html\HtmlElement;
use WysiwygCleaner\Html\HtmlNode;
use WysiwygCleaner\Html\HtmlText;

class ReworkCleaner
{
    /** @var array[] */
    private $whitespaceStyleRules;

    /** @var array[] */
    private $tagsRules;

    /**
     * @param array[] $whitespaceStyleRules
     * @param array[] $tagsRules
     */
    public function __construct(array $whitespaceStyleRules, array $tagsRules)
    {
        $this->whitespaceStyleRules = $whitespaceStyleRules;
        $this->tagsRules = $tagsRules;
    }

    /**
     * @param HtmlContainer $container
     *
     * @throws CleanerException
     */
    public function cleanup(HtmlContainer $container)
    {
        $children = [];

        foreach ($container->getChildren() as $node) {
            if ($node instanceof HtmlElement) {
                if ($node->getComputedStyle()->getDisplay() === CssDeclaration::DISPLAY_NONE) {
                    continue;
                }

                if ($node->getTag() === HtmlElement::TAG_BR) {
                    $cleanedStyle = CleanerUtils::cleanupStyle(
                        $this->whitespaceStyleRules,
                        $node->getComputedStyle(),
                        HtmlElement::TAG_BR
                    );

                    $children[] = new HtmlElement(
                        HtmlElement::TAG_BR,
                        $node->getAttributes(),
                        $cleanedStyle,
                        empty($node->getAttributes()) && $this->isStrippableWhitespace($cleanedStyle)
                    );
                } else {
                    $this->cleanup($node);
                    $children[] = $node;
                }
            } elseif ($node instanceof HtmlText) {
                if (!preg_match(
                    '/^([ ' . CleanerUtils::NBSP_CHARACTER . ']*+)?(.*?)([ ' . CleanerUtils::NBSP_CHARACTER . ']*)$/',
                    $this->cleanupInnerWhitespaces($node->getText()),
                    $mt
                )) {
                    throw new CleanerException('Internal error: regexp failed');
                }

                $whitespaceStyle = ($mt[1] !== '' || $mt[3] !== '')
                    ? CleanerUtils::cleanupStyle($this->whitespaceStyleRules, $node->getComputedStyle())
                    : null;

                $isStrippableWhitespace = ($whitespaceStyle === null
                    ? false
                    : $this->isStrippableWhitespace($whitespaceStyle)
                );

                if ($mt[1] !== '') {
                    $children[] = new HtmlText(' ', $whitespaceStyle, $isStrippableWhitespace);
                }

                if ($mt[2] !== '') {
                    $children[] = new HtmlText($mt[2], $node->getComputedStyle());
                }

                if ($mt[3] !== '') {
                    $children[] = new HtmlText(' ', $whitespaceStyle, $isStrippableWhitespace);
                }
            } else {
                throw new CleanerException(
                    'Doesn\'t know what to do with node "' . CleanerUtils::getClass($node) . '"'
                );
            }
        }

        $children = $this->stripWhitespaces($children);
        $children = $this->mergeWhitespaces($children);
        $children = $this->mergeTextNodes($children);
        $children = $this->cleanupEmptyNodes($children);

        $container->setChildren($children);
    }

    /**
     * @param string $text
     *
     * @return string
     */
    private function cleanupInnerWhitespaces(string $text) : string
    {
        $text = str_replace(CleanerUtils::BOM_CHARACTER, '', $text);

        // As this cleaner is not intended to support <pre>, we can treat tabs and newline characters as spaces
        /** @noinspection CascadeStringReplacementInspection */
        $text = str_replace(["\t", "\n", "\r"], ' ', $text);

        // Several &nbsp; can be used for indenting, but we will clean it
        $text = preg_replace('/[' . CleanerUtils::NBSP_CHARACTER . ']{2,}/u', CleanerUtils::NBSP_CHARACTER, $text);

        // &nbsp; with space has no typography sense, and can be used only for indenting (but we clean such cases)
        $text = str_replace(
            [CleanerUtils::NBSP_CHARACTER . ' ', ' ' . CleanerUtils::NBSP_CHARACTER],
            ' ',
            $text
        );

        return preg_replace('/[ ]{2,}/', ' ', $text);
    }

    /**
     * @param CssStyle $style
     *
     * @return bool
     */
    private function isStrippableWhitespace(CssStyle $style) : bool
    {
        foreach ($style->getDeclarations() as $property => $declaration) {
            if ($property !== CssDeclaration::PROP_DISPLAY) {
                return false;
            }
        }

        return $style->isInlineDisplay();
    }

    /**
     * @param HtmlNode[] $children
     *
     * @return array
     */
    private function stripWhitespaces(array $children) : array
    {
        while (!empty($children)) {
            if (!$children[0]->isStrippable()) {
                break;
            }

            array_shift($children);
        }

        while (!empty($children)) {
            if (!end($children)->isStrippable()) {
                break;
            }

            array_pop($children);
        }

        do {
            $hasChanges = false;

            for ($i = 0; $i < \count($children) - 1;) {
                $child = $children[$i];
                $nextChild = $children[$i + 1];

                if ($child->isStrippable()
                    && $nextChild->isStrippable()
                    && $child->getNodeType() === $nextChild->getNodeType()
                ) {
                    array_splice($children, $i + 1, 1);
                    $hasChanges = true;
                } else {
                    $i++;
                }
            }
        } while ($hasChanges);

        return $children;
    }

    /**
     * @param HtmlNode[] $children
     *
     * @return HtmlNode[]
     */
    private function mergeWhitespaces(array $children) : array
    {
        /** @noinspection CallableInLoopTerminationConditionInspection */
        for ($i = 0; $i < \count($children);) {
            $child = $children[$i];

            if (!($child instanceof HtmlText) || !$child->isStrippable()) {
                $i++;
                continue;
            }

            /** @var HtmlText $child */

            $prevChild = $children[$i - 1] ?? null;
            $nextChild = $children[$i + 1] ?? null;

            if ((($prevChild instanceof HtmlElement) && $prevChild->isStrippable())
                || (($nextChild instanceof HtmlElement) && $nextChild->isStrippable())
                || $prevChild->getComputedStyle()->isBlockyDisplay()
                || $nextChild->getComputedStyle()->isBlockyDisplay()
            ) {
                array_splice($children, $i, 1);
                continue;
            }

            if (($prevChild instanceof HtmlText)
                && ($nextChild instanceof HtmlText)
                && $prevChild->getComputedStyle()->compareTo($nextChild->getComputedStyle()) >= 0
            ) {
                $prevChild->appendText($child->getText());
                array_splice($children, $i, 1);
                continue;
            }

            $i++;
        }

        return $children;
    }

    /**
     * @param HtmlNode[] $children
     *
     * @return HtmlNode[]
     */
    private function mergeTextNodes(array $children) : array
    {
        for ($i = 0; $i < \count($children) - 1;) {
            $child = $children[$i];
            $nextChild = $children[$i + 1];

            $childStyle = $child->getComputedStyle();
            $nextChildStyle = $nextChild->getComputedStyle();

            if (($child instanceof HtmlText)
                && ($nextChild instanceof HtmlText)
                && $childStyle->isInlineDisplay()
                && $nextChildStyle->isInlineDisplay()
                && $childStyle->compareTo($nextChildStyle) === 0
            ) {
                $child->appendText($nextChild->getText());
                array_splice($children, $i + 1, 1);
                continue;
            }

            $i++;
        }

        return $children;
    }

    /**
     * @param HtmlNode[] $children
     *
     * @return HtmlNode[]
     * @throws CleanerException
     */
    private function cleanupEmptyNodes(array $children) : array
    {
        $result = [];

        foreach ($children as $child) {
            if (!($child instanceof HtmlElement)
                || !empty($child->getAttributes())
                || CleanerUtils::matchRules(
                    $this->tagsRules,
                    [$child->getTag(), 'blocky' => $child->getComputedStyle()->isBlockyDisplay()]
                )
            ) {
                $result[] = $child;
                continue;
            }

            if (empty($child->getChildren())) {
                continue;
            }

            if (!$child->getComputedStyle()->isBlockyDisplay()) {
                $result[] = $child;
                continue;
            }

            $hasNonBlockyInnerChild = false;

            foreach ($child->getChildren() as $innerChild) {
                if (!($innerChild instanceof HtmlElement) || !$innerChild->getComputedStyle()->isBlockyDisplay()) {
                    $hasNonBlockyInnerChild = true;
                    break;
                }
            }

            if ($hasNonBlockyInnerChild) {
                $result[] = $child;
                continue;
            }

            foreach ($child->getChildren() as $innerChild) {
                $result[] = $innerChild;
            }
        }

        return $result;
    }
}
