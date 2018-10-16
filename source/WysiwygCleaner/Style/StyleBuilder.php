<?php

namespace WysiwygCleaner\Style;

use WysiwygCleaner\CleanerException;
use WysiwygCleaner\Css\CssDeclaration;
use WysiwygCleaner\Css\CssParser;
use WysiwygCleaner\Css\CssSelector;
use WysiwygCleaner\Css\CssStyle;
use WysiwygCleaner\Css\CssStyleSheet;
use WysiwygCleaner\Html\HtmlContainer;
use WysiwygCleaner\Html\HtmlDocument;
use WysiwygCleaner\Html\HtmlElement;
use WysiwygCleaner\Html\HtmlText;
use WysiwygCleaner\TypeUtils;

class StyleBuilder
{
    private $cssParser;
    private $styleSheet;
    private $inlineDisplayDeclaration;

    public function __construct(CssParser $cssParser, CssStyleSheet $styleSheet)
    {
        $this->cssParser = $cssParser;
        $this->styleSheet = $styleSheet;

        $this->inlineDisplayDeclaration = new CssDeclaration(CssDeclaration::PROP_DISPLAY, CssDeclaration::DISPLAY_INLINE);
    }

    public function build(HtmlDocument $document)
    {
        $this->computeStyles($document, new CssSelector(), new CssStyle());
    }

    private function computeStyles(HtmlContainer $container, CssSelector $selector, CssStyle $computedStyle)
    {
        $textComputedStyle = null;

        if ($container instanceof HtmlElement) {
            $selector = $selector->combine(CssSelector::forElement($container));
            $cascadeStyle = clone $this->styleSheet->computeStyle($selector);

            if ($container->hasAttribute(HtmlElement::ATTR_STYLE)) {
                $cascadeStyle->appendAll($this->cssParser->parseStyle($container->getAttribute(HtmlElement::ATTR_STYLE)));
            }

            $computedStyle = clone $computedStyle;
            $computedStyle->extendAll($cascadeStyle);

            $container->setComputedStyle($computedStyle);
        } elseif (!($container instanceof HtmlDocument)) {
            throw new CleanerException('Doesn\'t know what to do with container "' . TypeUtils::getClass($container) . '"');
        }

        foreach ($container->getChildren() as $child) {
            if ($child instanceof HtmlElement) {
                $this->computeStyles($child, $selector, $computedStyle);
            } elseif ($child instanceof HtmlText) {
                if ($textComputedStyle === null) {
                    $textComputedStyle = clone $computedStyle;
                    $textComputedStyle->extend($this->inlineDisplayDeclaration);
                }

                $child->setComputedStyle($textComputedStyle);
            } else {
                throw new CleanerException('Doesn\'t know what to do with child "' . TypeUtils::getClass($child) . '"');
            }
        }
    }
}
