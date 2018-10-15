<?php

namespace WysiwygCleaner\Style;

use WysiwygCleaner\Css\CssDeclaration;
use WysiwygCleaner\Css\CssParser;
use WysiwygCleaner\Css\CssSelector;
use WysiwygCleaner\Css\CssStyle;
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

    public function __construct(CssParser $cssParser, string $userAgentStylesheet)
    {
        $this->cssParser = $cssParser;
        $this->styleSheet = $cssParser->parseStyleSheet($userAgentStylesheet);
        $this->inlineDisplayDeclaration = new CssDeclaration(CssDeclaration::PROP_DISPLAY, CssDeclaration::DISPLAY_INLINE, true);
    }

    public function build(HtmlDocument $document)
    {
        $this->computeStyles($document, new CssSelector(''), new CssStyle());
    }

    private function computeStyles(HtmlContainer $container, CssSelector $selector, CssStyle $computedStyle)
    {
        $computedStyle = clone $computedStyle;
        $textComputedStyle = null;

        if ($container instanceof HtmlElement) {
            $selector = $this->getCombinedSelector($selector, $container);
            $computedStyle->appendAll($this->styleSheet->computeStyle($selector));

            if ($container->hasAttribute(HtmlElement::ATTR_STYLE)) {
                $computedStyle->appendAll($this->cssParser->parseStyle($container->getAttribute(HtmlElement::ATTR_STYLE)));
            }

            $container->setComputedStyle($computedStyle);
        } elseif (!($container instanceof HtmlDocument)) {
            throw new ParserException('Doesn\'t know what to do with container "' . TypeUtils::getClass($container) . '"');
        }

        foreach ($container->getChildren() as $child) {
            if ($child instanceof HtmlElement) {
                $this->computeStyles($child, $selector, $computedStyle);
            } elseif ($child instanceof HtmlText) {
                if ($textComputedStyle === null) {
                    $textComputedStyle = clone $computedStyle;
                    $textComputedStyle->append($this->inlineDisplayDeclaration);
                }

                $child->setComputedStyle($textComputedStyle);
            } else {
                throw new ParserException('Doesn\'t know what to do with child "' . TypeUtils::getClass($child) . '"');
            }
        }
    }

    private function getCombinedSelector(CssSelector $baseSelector, HtmlElement $element) : CssSelector
    {
        return new CssSelector($element->getTag());
    }
}
