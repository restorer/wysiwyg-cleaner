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

    /** @var string[] */
    private $keepWhitespacePropertiesRexegps;

    /** @var CssRenderer */
    private $cssRenderer;

    /** @var CssStyleSheet */
    private $styleSheet;

    /** @var CssDeclaration */
    private $inlineDisplayDeclaration;

    /**
     * @param array $preferableTags
     * @param array $keepWhitespacePropertiesRexegps
     * @param CssRenderer $cssRenderer
     * @param CssStyleSheet $styleSheet
     */
    public function __construct(
        array $preferableTags,
        array $keepWhitespacePropertiesRexegps,
        CssRenderer $cssRenderer,
        CssStyleSheet $styleSheet
    ) {
        $this->preferableTags = array_map('\strtolower', $preferableTags);
        $this->keepWhitespacePropertiesRexegps = $keepWhitespacePropertiesRexegps;
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
        $this->reconstructStyles($document, new CssSelector());
        $this->reconstructAttributes($document);
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

    /**
     * @param HtmlContainer $container
     * @param CssSelector $selector
     *
     * @throws CleanerException
     */
    private function reconstructStyles(HtmlContainer $container, CssSelector $selector)
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
            $baseChildStyle = $this->styleSheet->resolveStyle($childSelector);
            $reconstructedChildStyle = new CssStyle();

            foreach ($child->getComputedStyle()->getDeclarations() as $property => $declaration) {
                if (!$baseChildStyle->hasProperty($property)
                    || $declaration->getExpression() !== $baseChildStyle->getDeclaration($property)->getExpression()
                ) {
                    $reconstructedChildStyle->append($declaration);
                }
            }

            $child->setComputedStyle($reconstructedChildStyle);
            $this->reconstructStyles($child, $childSelector);
        }
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
                    ->compareProperties($child->getComputedStyle());

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
     */
    private function reconstructStructure(HtmlContainer $container)
    {
        $initialTextStyle = ($container instanceof HtmlElement)
            ? (clone $container->getComputedStyle())
            : new CssStyle();

        $initialTextStyle->extend($this->inlineDisplayDeclaration);

        $children = [];
        $elementsStack = [];

        foreach ($container->getChildren() as $child) {
            $isStrippableLineBreak = CleanerUtils::isStrippableLineBreak(
                $child,
                $this->keepWhitespacePropertiesRexegps
            );

            if (!($child instanceof HtmlText) && !$isStrippableLineBreak) {
                if ($child instanceof HtmlContainer) {
                    $this->reconstructStructure($child);
                }

                $children[] = $child;
                $elementsStack = [];
                continue;
            }

            /** @var HtmlElement|null $intoElement */
            /** @var int $stylesDiff */

            for (; ;) {
                if (empty($elementsStack)) {
                    $intoElement = null;
                    $stylesDiff = $initialTextStyle->compareProperties($child->getComputedStyle());
                    break;
                }

                $intoElement = end($elementsStack);
                $stylesDiff = $intoElement->getComputedStyle()->compareProperties($child->getComputedStyle());

                if ($stylesDiff >= 0 || $isStrippableLineBreak) {
                    break;
                }

                array_pop($elementsStack);
            }

            if ($stylesDiff > 0 && !$isStrippableLineBreak) {
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

        $container->setChildren($children);
    }
}
