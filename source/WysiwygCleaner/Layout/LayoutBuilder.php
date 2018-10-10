<?php

namespace WysiwygCleaner\Layout;

use WysiwygCleaner\Css\CssParser;
use WysiwygCleaner\Css\CssRuleSet;
use WysiwygCleaner\Css\CssSelector;
use WysiwygCleaner\Css\CssUtils;
use WysiwygCleaner\Html\HtmlContainer;
use WysiwygCleaner\Html\HtmlDocument;
use WysiwygCleaner\Html\HtmlElement;
use WysiwygCleaner\Html\HtmlText;
use WysiwygCleaner\ParserException;
use WysiwygCleaner\TypeUtils;

class LayoutBuilder
{
    private $cssParser;
    private $baseStylesheet;

    public function __construct(CssParser $cssParser, string $userAgentStylesheet)
    {
        $this->cssParser = $cssParser;
        $this->baseStylesheet = $cssParser->parseStyleSheet($userAgentStylesheet);
    }

    public function build(HtmlDocument $document) : LayoutBox
    {
        $this->computeStyles($document, new CssSelector(''), new CssRuleSet());
        $box = $this->buildLayoutTree($document);

        if ($box->isInline()) {
            throw new ParserException('Internal error: root box must have block context');
        }

        return $box;
    }

    private function buildLayoutTree(HtmlContainer $container) : LayoutBox
    {
        if ($container instanceof HtmlDocument) {
            $box = new LayoutBox(true);
        } elseif ($container instanceof HtmlElement) {
            $display = $this->getDisplayValue($container);

            if ($display === CssUtils::DISPLAY_NONE) {
                throw new ParserException('Internal error: root node shouldn\'t have display "none"');
            }

            $box = new LayoutBox($display === CssUtils::DISPLAY_BLOCK, $container);
        } else {
            throw new ParserException('Doesn\'t know what to do with container "' . TypeUtils::getClass($container) . '"');
        }

        foreach ($container->getChildren() as $child) {
            if ($child instanceof HtmlElement) {
                switch ($this->getDisplayValue($child)) {
                    case CssUtils::DISPLAY_NONE:
                        // Skip
                        break;

                    case CssUtils::DISPLAY_BLOCK:
                        $box->getBlockContainer()->appendChild($this->buildLayoutTree($child));
                        break;

                    default:
                        $box->getInlineContainer()->appendChild($this->buildLayoutTree($child));
                }
            } elseif ($child instanceof HtmlText) {
                $box->getInlineContainer()->appendChild(new LayoutText($child->getText()));
            } else {
                throw new ParserException('Doesn\'t know what to do with child "' . TypeUtils::getClass($child) . '"');
            }
        }

        return $box;
    }

    private function computeStyles(HtmlContainer $container, CssSelector $selector, CssRuleSet $computedStyle)
    {
        if ($container instanceof HtmlElement) {
            // TODO: implement correct style merging (currently it may work incorrectly for !important properties)

            $selector = $this->generateNestedSelector($selector, $container);
            $computedStyle = $computedStyle->concat($this->baseStylesheet->computeStyle($selector));

            if ($container->hasAttribute(CssUtils::ATTR_STYLE)) {
                $computedStyle = $computedStyle->concat($this->cssParser->parseRuleSet($container->getAttribute(CssUtils::ATTR_STYLE)));
            }

            $container->setComputedStyle($computedStyle);
        } elseif (!($container instanceof HtmlDocument)) {
            throw new ParserException('Doesn\'t know what to do with container "' . TypeUtils::getClass($container) . '"');
        }

        foreach ($container->getChildren() as $child) {
            if ($child instanceof HtmlElement) {
                $this->computeStyles($child, $selector, $computedStyle);
            } elseif (!($child instanceof HtmlText)) {
                throw new ParserException('Doesn\'t know what to do with child "' . TypeUtils::getClass($child) . '"');
            }
        }
    }

    private function getDisplayValue(HtmlElement $element) : string
    {
        return \strtolower($element->getComputedStyle()->getRuleValue(CssUtils::PROP_DISPLAY, ''));
    }

    private function generateNestedSelector(CssSelector $selector, HtmlElement $element) : CssSelector
    {
        // TODO: support something more complex than just tag name
        return new CssSelector($element->getTag());
    }
}
