<?php

namespace WysiwygCleaner\Rework;

use WysiwygCleaner\CleanerException;
use WysiwygCleaner\Css\CssDeclaration;
use WysiwygCleaner\Css\CssSelector;
use WysiwygCleaner\Css\CssStyle;
use WysiwygCleaner\Css\CssStyleSheet;
use WysiwygCleaner\Html\HtmlContainer;
use WysiwygCleaner\Html\HtmlDocument;
use WysiwygCleaner\Html\HtmlElement;
use WysiwygCleaner\Html\HtmlNode;
use WysiwygCleaner\Html\HtmlText;
use WysiwygCleaner\TypeUtils;

class ReworkReconstructor
{
    const FALLBACK_TAG = 'span';

    private $preferrableTags;
    private $keepWhitespaceProperties;
    private $styleSheet;
    private $inlineDisplayDeclaration;

    public function __construct(array $preferrableTags, array $keepWhitespaceProperties, CssStyleSheet $styleSheet)
    {
        $this->preferrableTags = array_map('\strtolower', $preferrableTags);
        $this->keepWhitespaceProperties = $keepWhitespaceProperties;
        $this->styleSheet = $styleSheet;

        $this->inlineDisplayDeclaration = new CssDeclaration(CssDeclaration::PROP_DISPLAY, CssDeclaration::DISPLAY_INLINE);
    }

    public function reconstruct(HtmlDocument $document)
    {
        $this->reconstructStructure($document);
        $this->reconstructTags($document);
        $this->reconstructStyles($document, new CssSelector());
    }

    private function reconstructStyles(HtmlContainer $container, CssSelector $selector)
    {
        foreach ($container->getChildren() as $child) {
            if ($child instanceof HtmlText) {
                $child->setComputedStyle(new CssStyle());
                continue;
            }

            if (!($child instanceof HtmlElement)) {
                throw new CleanerException('Doesn\'t know what to do with child "' . TypeUtils::getClass($child) . '"');
            }

            $childSelector = $selector->combine(CssSelector::forElement($child));
            $baseChildStyle = $this->styleSheet->computeStyle($childSelector);
            $reconstructedChildStyle = new CssStyle();

            foreach ($child->getComputedStyle()->getDeclarations() as $declaration) {
                $property = $declaration->getProperty();

                if (!$baseChildStyle->hasDeclaration($property)
                    || $declaration->getExpression() !== $baseChildStyle->getDeclaration($property)->getExpression()
                ) {
                    $reconstructedChildStyle->append($declaration);
                }
            }

            $child->setComputedStyle($reconstructedChildStyle);
            $this->reconstructStyles($child, $childSelector);
        }
    }

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

            foreach ($this->preferrableTags as $tag) {
                $stylesDiff = $this->compareStyles(
                    $this->styleSheet->computeStyle(CssSelector::forTagName($tag)),
                    $child->getComputedStyle()
                );

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

    private function reconstructStructure(HtmlContainer $container)
    {
        $initialTextStyle = (($container instanceof HtmlElement) ? (clone $container->getComputedStyle()) : new CssStyle());
        $initialTextStyle->extend($this->inlineDisplayDeclaration);

        $children = [];
        $elementsStack = [];

        foreach ($container->getChildren() as $child) {
            $isStrippableBr = $this->isStrippableBr($child);

            if (!($child instanceof HtmlText) && !$isStrippableBr) {
                if ($child instanceof HtmlContainer) {
                    $this->reconstructStructure($child);
                }

                $children[] = $child;
                $elementsStack = [];
                continue;
            }

            /** @var HtmlElement|null $intoElement */

            for (;;) {
                if (empty($elementsStack)) {
                    $intoElement = null;
                    $stylesDiff = $this->compareStyles($initialTextStyle, $child->getComputedStyle());
                    break;
                }

                $intoElement = end($elementsStack);
                $stylesDiff = $this->compareStyles($intoElement->getComputedStyle(), $child->getComputedStyle());

                if ($stylesDiff >= 0 || $isStrippableBr) {
                    break;
                }

                array_pop($elementsStack);
            }

            if ($stylesDiff > 0 && !$isStrippableBr) {
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

    private function compareStyles(CssStyle $source, CssStyle $destination) : int
    {
        foreach ($source->getDeclarations() as $declaration) {
            if (!$destination->hasDeclaration($declaration->getProperty())) {
                return -1;
            }
        }

        $diff = 0;

        foreach ($destination->getDeclarations() as $declaration) {
            if (!$source->hasDeclaration($declaration->getProperty())) {
                $diff++;
            }
        }

        return $diff;
    }

    private function isStrippableBr(HtmlNode $node) : bool
    {
        return (($node instanceof HtmlElement)
            && ($node->getTag() === HtmlElement::TAG_BR)
            && !$this->shouldKeepStyle($node->getComputedStyle())
        );
    }

    private function shouldKeepStyle(CssStyle $style) : bool
    {
        foreach ($style->getDeclarations() as $declaration) {
            $property = $declaration->getProperty();

            if ($property === CssDeclaration::PROP_DISPLAY && $declaration->getExpression() === CssDeclaration::DISPLAY_INLINE) {
                continue;
            }

            foreach ($this->keepWhitespaceProperties as $regexp) {
                if (preg_match($regexp, $property)) {
                    return true;
                }
            }
        }

        return false;
    }
}
