<?php

namespace WysiwygCleaner\Layout;

use WysiwygCleaner\Css\CssParser;
use WysiwygCleaner\Css\CssRuleSet;
use WysiwygCleaner\Css\CssSelector;
use WysiwygCleaner\Html\HtmlContainer;
use WysiwygCleaner\Html\HtmlDocument;
use WysiwygCleaner\Html\HtmlElement;
use WysiwygCleaner\Html\HtmlText;
use WysiwygCleaner\ParserException;
use WysiwygCleaner\TypeUtils;

class LayoutBuilder
{
    const ATTR_STYLE = 'style';
    const PROP_DISPLAY = 'display';
    const DISPLAY_BLOCK = 'block';
    const DISPLAY_NONE = 'none';

    private $cssParser;
    private $baseStylesheet;

    public function __construct(CssParser $cssParser, string $userAgentStylesheet)
    {
        $this->cssParser = $cssParser;
        $this->baseStylesheet = $cssParser->parseStyleSheet($userAgentStylesheet);
    }

    public function build(HtmlDocument $document) : LayoutBlockBox
    {
        $this->computeStyles($document, new CssSelector(''), new CssRuleSet());
        $box = $this->buildLayoutTree($document);

        if (!($box instanceof LayoutBlockBox)) {
            throw new ParserException('Internal error: LayoutBlockBox expected, but "' . TypeUtils::getClass($box) . '" given');
        }

        return $box;
    }

    private function buildLayoutTree(HtmlContainer $container) : LayoutBox
    {
        if ($container instanceof HtmlDocument) {
            $box = new LayoutBlockBox('', new CssRuleSet());
        } elseif ($container instanceof HtmlElement) {
            switch (\strtolower($container->getComputedStyle()->getRuleValue(self::PROP_DISPLAY, ''))) {
                case self::DISPLAY_NONE:
                    throw new ParserException('Internal error: root node shouldn\'t have display "none"');

                case self::DISPLAY_BLOCK:
                    $box = new LayoutBlockBox($container->getTag(), $container->getComputedStyle());
                    break;

                default:
                    $box = new LayoutInlineBox($container->getTag(), $container->getComputedStyle());
            }
        } else {
            throw new ParserException('Doesn\'t know what to do with container "' . TypeUtils::getClass($container) . '"');
        }

        $resultBox = $box;
        echo "\n" . $resultBox->prettyDump();

        foreach ($container->getChildren() as $child) {
            echo "\n" . $child->prettyDump();

            if ($child instanceof HtmlElement) {
                switch (\strtolower($child->getComputedStyle()->getRuleValue(self::PROP_DISPLAY, ''))) {
                    case self::DISPLAY_NONE:
                        // Skip
                        break;

                    case self::DISPLAY_BLOCK:
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

            echo "\n" . $resultBox->prettyDump();
        }

        return $resultBox;
    }

    private function computeStyles(HtmlContainer $container, CssSelector $selector, CssRuleSet $inlineStyle)
    {
        if ($container instanceof HtmlElement) {
            if ($container->hasAttribute(self::ATTR_STYLE)) {
                $inlineStyle = $inlineStyle->concat($this->cssParser->parseRuleSet($container->getAttribute(self::ATTR_STYLE)));
            }

            $selector = $this->generateNestedSelector($selector, $container);
            $container->setComputedStyle($this->baseStylesheet->computeStyle($selector)->concat($inlineStyle));
        } elseif (!($container instanceof HtmlDocument)) {
            throw new ParserException('Doesn\'t know what to do with container "' . TypeUtils::getClass($container) . '"');
        }

        foreach ($container->getChildren() as $child) {
            if ($child instanceof HtmlElement) {
                $this->computeStyles($child, $selector, $inlineStyle);
            } elseif (!($child instanceof HtmlText)) {
                throw new ParserException('Doesn\'t know what to do with child "' . TypeUtils::getClass($child) . '"');
            }
        }
    }

    private function generateNestedSelector(CssSelector $selector, HtmlElement $element) : CssSelector
    {
        // TODO: support something more complex than just tag name
        return new CssSelector($element->getTag());
    }
}
