<?php

namespace WysiwygCleaner\Rework;

use WysiwygCleaner\CleanerException;
use WysiwygCleaner\CleanerUtils;
use WysiwygCleaner\Css\CssDeclaration;
use WysiwygCleaner\Css\CssRenderer;
use WysiwygCleaner\Css\CssSelector;
use WysiwygCleaner\Css\CssStyle;
use WysiwygCleaner\Css\CssStyleSheet;
use WysiwygCleaner\Html\HtmlContainer;
use WysiwygCleaner\Html\HtmlDocument;
use WysiwygCleaner\Html\HtmlElement;
use WysiwygCleaner\Html\HtmlText;

class ReworkReconstructor
{
    const FALLBACK_TAG = 'span';

    /** @var string[] */
    private $preferableTags;

    /** @var CssRenderer */
    private $cssRenderer;

    /** @var CssStyleSheet */
    private $styleSheet;

    /** @var CssDeclaration */
    private $inlineDisplayDeclaration;

    /**
     * @param array $preferableTags
     * @param CssRenderer $cssRenderer
     * @param CssStyleSheet $styleSheet
     */
    public function __construct(
        array $preferableTags,
        CssRenderer $cssRenderer,
        CssStyleSheet $styleSheet
    ) {
        $this->preferableTags = array_map('\strtolower', $preferableTags);
        $this->cssRenderer = $cssRenderer;
        $this->styleSheet = $styleSheet;

        $this->inlineDisplayDeclaration = new CssDeclaration(
            CssDeclaration::PROP_DISPLAY,
            CssDeclaration::DISPLAY_INLINE
        );
    }

    /**
     * @param HtmlDocument $document
     *
     * @throws CleanerException
     */
    public function reconstruct(HtmlDocument $document)
    {
        $this->reconstructStructure($document);
        $this->reconstructTags($document);
        $this->reconstructStyles($document, new CssSelector(), new CssStyle());
        $this->reconstructAttributes($document);
    }

    /**
     * @param HtmlContainer $container
     */
    private function reconstructStructure(HtmlContainer $container)
    {
        $initialTextStyle = ($container instanceof HtmlElement)
            ? (clone $container->getComputedStyle())
            : new CssStyle();

        $initialTextStyle->extend($this->inlineDisplayDeclaration);

        $children = [];
        $elementsStack = [];
        $pendingStrippableNode = null;

        /** @noinspection ForeachInvariantsInspection */
        for ($i = 0, $len = \count($container->getChildren()); $i < $len; $i++) {
            $child = $container->getChildren()[$i];
            $isStrippableLineBreak = ($child instanceof HtmlElement) && $child->isStrippable();

            if ($isStrippableLineBreak) {
                $prevChild = ($i > 0 ? $container->getChildren()[$i - 1] : null);
                $nextChild = ($i + 1 < $len ? $container->getChildren()[$i + 1] : null);

                if (!($prevChild instanceof HtmlText)
                    || !($nextChild instanceof HtmlText)
                    || $prevChild->getComputedStyle()->compareTo($nextChild->getComputedStyle()) < 0
                ) {
                    $isStrippableLineBreak = false;
                }
            }

            if (!($child instanceof HtmlText) && !$isStrippableLineBreak) {
                if ($child instanceof HtmlContainer) {
                    $this->reconstructStructure($child);
                }

                $children[] = $child;
                $elementsStack = [];
                continue;
            }

            $childStyle = $child->getComputedStyle();

            if (($child instanceof HtmlText) && !$childStyle->isInlineDisplay()) {
                $wrapperElement = new HtmlElement('', null, $childStyle);
                $wrapperElement->appendChild($child);

                $children[] = $child;
                $elementsStack = [];
                continue;
            }

            if ($child->isStrippable()) {
                if ($pendingStrippableNode !== null) {
                    // Should not happen, but...
                    $children[] = $pendingStrippableNode;
                    $elementsStack = [];
                }

                $pendingStrippableNode = $child;
                continue;
            }

            /** @var HtmlElement|null $intoElement */
            /** @var int $stylesDiff */

            for (; ;) {
                $intoElement = empty($elementsStack) ? null : end($elementsStack);
                $stylesDiff = ($intoElement === null ? $initialTextStyle : $intoElement->getComputedStyle())->compareTo($childStyle);

                if ($intoElement === null || $stylesDiff >= 0) {
                    break;
                }

                array_pop($elementsStack);
            }

            if ($pendingStrippableNode !== null) {
                if ($intoElement === null) {
                    $children[] = $pendingStrippableNode;
                } else {
                    $intoElement->appendChild($pendingStrippableNode);
                }

                $pendingStrippableNode = null;
            }

            if ($stylesDiff != 0) {
                $wrapperElement = new HtmlElement('', null, $child->getComputedStyle());
                $elementsStack[] = $wrapperElement;

                if ($intoElement === null) {
                    $children[] = $wrapperElement;
                } else {
                    $intoElement->appendChild($wrapperElement);
                }

                $intoElement = $wrapperElement;
            }

            if ($intoElement === null) {
                $children[] = $child;
            } else {
                $intoElement->appendChild($child);
            }
        }

        if ($pendingStrippableNode !== null) {
            // Should not happen, but...
            $children[] = $pendingStrippableNode;
        }

        if (($container instanceof HtmlElement)
            && \count($children) === 1
            && ($children[0] instanceof HtmlElement)
            && $children[0]->getTag() === ''
        ) {
            /** @var CssStyle $mergedBlockStyle */
            $mergedBlockStyle = $children[0]->getComputedStyle();
            $mergedBlockStyle->extend($container->getComputedStyle()->getDeclaration(CssDeclaration::PROP_DISPLAY));

            $container->setComputedStyle($mergedBlockStyle);
            $children = $children[0]->getChildren();
        }

        $container->setChildren($children);
    }

    /**
     * @param HtmlContainer $container
     */
    private function reconstructTags(HtmlContainer $container)
    {
        foreach ($container->getChildren() as $child) {
            if ($child instanceof HtmlContainer) {
                $this->reconstructTags($child);
            }

            if (!($child instanceof HtmlElement) || $child->getTag() !== '') {
                continue;
            }

            $matchedTag = self::FALLBACK_TAG;
            $matchedStylesDiff = null;

            foreach ($this->preferableTags as $tag) {
                $stylesDiff = $this->styleSheet
                    ->resolveStyle(CssSelector::forTagName($tag))
                    ->compareTo($child->getComputedStyle());

                if ($matchedStylesDiff === null
                    || $matchedStylesDiff < 0
                    || ($stylesDiff >= 0 && $stylesDiff < $matchedStylesDiff)
                ) {
                    $matchedTag = $tag;
                    $matchedStylesDiff = $stylesDiff;
                }
            }

            $child->setTag($matchedTag);
        }
    }

    /**
     * @param HtmlContainer $container
     * @param CssSelector $selector
     * @param CssStyle $inheritedStyle
     *
     * @throws CleanerException
     */
    private function reconstructStyles(HtmlContainer $container, CssSelector $selector, CssStyle $inheritedStyle)
    {
        foreach ($container->getChildren() as $child) {
            if ($child instanceof HtmlText) {
                $child->setComputedStyle(new CssStyle());
                continue;
            }

            if (!($child instanceof HtmlElement)) {
                throw new CleanerException(
                    'Doesn\'t know what to do with child "' . CleanerUtils::getClass($child) . '"'
                );
            }

            $childSelector = $selector->combine(CssSelector::forElement($child));
            $childComputedStyle = $child->getComputedStyle();
            $reconstructedChildStyle = new CssStyle();

            $inheritedStyle = clone $inheritedStyle;
            $inheritedStyle->extendAll($this->styleSheet->resolveStyle($childSelector));

            foreach ($childComputedStyle->getDeclarations() as $property => $declaration) {
                if (!$inheritedStyle->hasProperty($property)
                    || !$declaration->equals($inheritedStyle->getDeclaration($property))
                ) {
                    $reconstructedChildStyle->append($declaration);
                }
            }

            $child->setComputedStyle($reconstructedChildStyle);
            $this->reconstructStyles($child, $childSelector, $childComputedStyle);
        }
    }

    /**
     * @param HtmlContainer $container
     *
     * @throws CleanerException
     */
    private function reconstructAttributes(HtmlContainer $container)
    {
        foreach ($container->getChildren() as $child) {
            if (!($child instanceof HtmlElement)) {
                continue;
            }

            $renderedStyle = $this->cssRenderer->renderStyle($child->getComputedStyle());

            if ($renderedStyle === '') {
                $child->removeAttribute(HtmlElement::ATTR_STYLE);
            } else {
                $child->setAttribute(HtmlElement::ATTR_STYLE, $renderedStyle);
            }

            $child->setComputedStyle(new CssStyle());
            $this->reconstructAttributes($child);
        }
    }
}
