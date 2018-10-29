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
    /** @var string[] */
    private $keepWhitespacePropertiesRegexps;

    /** @var string[] */
    private $flattenBlockTags;

    /** @var string[] */
    private $removeStylesRegexps;

    /** @var string[] */
    private $removeBlockStylesRegexps;

    /**
     * @param array $keepWhitespacePropertiesRegexps
     * @param array $flattenBlockTags
     * @param array $removeStylesRegexps
     * @param array $removeBlockStylesRegexps
     */
    public function __construct(
        array $keepWhitespacePropertiesRegexps,
        array $flattenBlockTags,
        array $removeStylesRegexps,
        array $removeBlockStylesRegexps
    ) {
        $this->keepWhitespacePropertiesRegexps = $keepWhitespacePropertiesRegexps;
        $this->flattenBlockTags = \array_map('\strtolower', $flattenBlockTags);
        $this->removeStylesRegexps = $removeStylesRegexps;
        $this->removeBlockStylesRegexps = $removeBlockStylesRegexps;
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
                    $children[] = new HtmlElement(
                        HtmlElement::TAG_BR,
                        $node->getAttributes(),
                        $this->cleanupWhitespaceStyle($node->getComputedStyle())
                    );
                } else {
                    $this->cleanup($node);
                    $node->setComputedStyle($this->cleanupStyle($node->getComputedStyle()));
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
                    ? $this->cleanupWhitespaceStyle($node->getComputedStyle())
                    : null;

                if ($mt[1] !== '') {
                    $children[] = new HtmlText(' ', $whitespaceStyle);
                }

                if ($mt[2] !== '') {
                    $children[] = new HtmlText($mt[2], $this->cleanupStyle($node->getComputedStyle()));
                }

                if ($mt[3] !== '') {
                    $children[] = new HtmlText(' ', $whitespaceStyle);
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
        $children = $this->removeEmptyChildren($children);

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
     * @param HtmlNode[] $children
     *
     * @return HtmlNode[]
     */
    private function removeEmptyChildren(array $children) : array
    {
        $result = [];

        foreach ($children as $child) {
            if (!($child instanceof HtmlElement)
                || !empty($child->getAttributes())
                || !\in_array($child->getTag(), $this->flattenBlockTags, true)
            ) {
                $result[] = $child;
                continue;
            }

            if (empty($child->getChildren())) {
                continue;
            }

            if (CleanerUtils::isInlineDisplay($child->getComputedStyle()->getDisplay())) {
                $result[] = $child;
                continue;
            }

            $hasInnerInlineChild = false;

            foreach ($child->getChildren() as $innerChild) {
                if (CleanerUtils::isInlineDisplay($innerChild->getComputedStyle()->getDisplay())) {
                    $hasInnerInlineChild = true;
                    break;
                }
            }

            if ($hasInnerInlineChild) {
                $result[] = $child;
                continue;
            }

            foreach ($child->getChildren() as $innerChild) {
                $result[] = $innerChild;
            }
        }

        return $result;
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

            if (($child instanceof HtmlText)
                && ($nextChild instanceof HtmlText)
                && CleanerUtils::isInlineDisplay($child->getComputedStyle()->getDisplay())
                && CleanerUtils::isInlineDisplay($nextChild->getComputedStyle()->getDisplay())
                && $child->getComputedStyle()->visuallyEquals($nextChild->getComputedStyle())
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
     */
    private function mergeWhitespaces(array $children) : array
    {
        /** @noinspection CallableInLoopTerminationConditionInspection */
        for ($i = 0; $i < \count($children);) {
            $child = $children[$i];

            if (!$this->isStrippableSpace($child)) {
                $i++;
                continue;
            }

            /** @var HtmlText $child */

            $prevChild = $children[$i - 1] ?? null;
            $nextChild = $children[$i + 1] ?? null;

            if ($this->isBlockyElement($prevChild)
                || $this->isBlockyElement($nextChild)
                || $this->isStrippableLineBreak($prevChild)
                || $this->isStrippableLineBreak($nextChild)
            ) {
                array_splice($children, $i, 1);
                continue;
            }

            if (($prevChild instanceof HtmlText)
                && ($nextChild instanceof HtmlText)
                && $prevChild->getComputedStyle()->compareProperties($nextChild->getComputedStyle()) >= 0
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
     * @param HtmlNode|null $node
     *
     * @return bool
     */
    private function isBlockyElement($node) : bool
    {
        return ($node instanceof HtmlElement) && CleanerUtils::isBlockyDisplay($node->getComputedStyle()->getDisplay());
    }

    /**
     * @param array $children
     *
     * @return array
     */
    private function stripWhitespaces(array $children) : array
    {
        while (!empty($children)) {
            $child = $children[0];

            if (!$this->isStrippableSpace($child) && !$this->isStrippableLineBreak($child)) {
                break;
            }

            array_shift($children);
        }

        while (!empty($children)) {
            $child = end($children);

            if (!$this->isStrippableSpace($child) && !$this->isStrippableLineBreak($child)) {
                break;
            }

            array_pop($children);
        }

        do {
            $hasChanges = false;

            for ($i = 0; $i < \count($children) - 1;) {
                $child = $children[$i];
                $nextChild = $children[$i + 1];

                if (($this->isStrippableSpace($child) && $this->isStrippableSpace($nextChild))
                    || ($this->isStrippableLineBreak($child) && $this->isStrippableLineBreak($nextChild))
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
     * @param CssStyle $style
     *
     * @return CssStyle
     */
    private function cleanupStyle(CssStyle $style) : CssStyle
    {
        $result = new CssStyle();
        $isInline = CleanerUtils::isInlineDisplay($style->getDisplay());

        foreach ($style->getDeclarations() as $property => $declaration) {
            if (!CleanerUtils::matchRegexps($property, $this->removeStylesRegexps)
                && ($isInline || !CleanerUtils::matchRegexps($property, $this->removeBlockStylesRegexps))
            ) {
                $result->append($declaration);
            }
        }

        return $result;
    }

    /**
     * @param CssStyle $style
     *
     * @return CssStyle
     */
    private function cleanupWhitespaceStyle(CssStyle $style) : CssStyle
    {
        $result = new CssStyle();

        foreach ($style->getDeclarations() as $property => $declaration) {
            if ($property === CssDeclaration::PROP_DISPLAY) {
                $result->append($declaration);
                continue;
            }

            foreach ($this->keepWhitespacePropertiesRegexps as $regexp) {
                if (preg_match($regexp, $property)) {
                    $result->append($declaration);
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * @param HtmlNode $node
     *
     * @return bool
     */
    private function isStrippableSpace(HtmlNode $node) : bool
    {
        return (($node instanceof HtmlText)
            && ($node->getText() === ' ')
            && CleanerUtils::canStripWhitespaceStyle($node->getComputedStyle(), $this->keepWhitespacePropertiesRegexps)
        );
    }

    /**
     * @param HtmlNode $node
     *
     * @return bool
     */
    private function isStrippableLineBreak(HtmlNode $node) : bool
    {
        return CleanerUtils::isStrippableLineBreak($node, $this->keepWhitespacePropertiesRegexps);
    }
}
