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
    private $keepWhitespacePropertiesRexegps;

    /**
     * @param array $keepWhitespacePropertiesRegexps
     */
    public function __construct(array $keepWhitespacePropertiesRegexps)
    {
        $this->keepWhitespacePropertiesRexegps = $keepWhitespacePropertiesRegexps;
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
                    $children[] = new HtmlText($mt[2], $node->getComputedStyle());
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

        $container->setChildren($children);
    }

    /**
     * @param string $text
     *
     * @return string
     */
    private function cleanupInnerWhitespaces(string $text) : string
    {
        // As this cleaner is not intended to support <pre>, we can treat tabs and newline characters as spaces
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

            $prevMergeFactor = $this->computeWhitespaceMergeFactor($child->getComputedStyle(), $prevChild);
            $nextMergeFactor = $this->computeWhitespaceMergeFactor($child->getComputedStyle(), $nextChild);

            if ($prevMergeFactor >= 0 && ($nextMergeFactor < 0 || $prevMergeFactor <= $nextMergeFactor)) {
                /** @var HtmlText $prevChild */

                $prevChild->appendText($child->getText());
                array_splice($children, $i, 1);
                continue;
            }

            if ($nextMergeFactor >= 0) {
                /** @var HtmlText $nextChild */

                $nextChild->prependText($child->getText());
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
     * @param CssStyle $whitespaceStyle
     * @param HtmlNode|null $node
     *
     * @return int
     */
    private function computeWhitespaceMergeFactor(CssStyle $whitespaceStyle, $node) : int
    {
        if (!($node instanceof HtmlText)) {
            return -1;
        }

        $nodeStyle = $node->getComputedStyle();

        if (!CleanerUtils::isInlineDisplay($nodeStyle->getDisplay())) {
            return -1;
        }


        foreach ($whitespaceStyle->getDeclarations() as $property => $declaration) {
            if (!$nodeStyle->hasProperty($property)
                || $declaration->getExpression() !== $nodeStyle->getExpression($property)
            ) {
                return -1;
            }
        }

        return $whitespaceStyle->compareProperties($nodeStyle);
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
    private function cleanupWhitespaceStyle(CssStyle $style) : CssStyle
    {
        $result = new CssStyle();

        foreach ($style->getDeclarations() as $property => $declaration) {
            if ($property === CssDeclaration::PROP_DISPLAY) {
                $result->append($declaration);
                continue;
            }

            foreach ($this->keepWhitespacePropertiesRexegps as $regexp) {
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
            && CleanerUtils::canStripWhitespaceStyle($node->getComputedStyle(), $this->keepWhitespacePropertiesRexegps)
        );
    }

    /**
     * @param HtmlNode $node
     *
     * @return bool
     */
    private function isStrippableLineBreak(HtmlNode $node) : bool
    {
        return CleanerUtils::isStrippableLineBreak($node, $this->keepWhitespacePropertiesRexegps);
    }
}
