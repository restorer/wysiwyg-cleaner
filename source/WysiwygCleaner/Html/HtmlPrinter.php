<?php

namespace WysiwygCleaner\Html;

use WysiwygCleaner\CleanerException;
use WysiwygCleaner\Css\CssSelector;
use WysiwygCleaner\Css\CssStyleSheet;
use WysiwygCleaner\Css\CssPrinter;
use WysiwygCleaner\TypeUtils;

class HtmlPrinter
{
    const VOID_TAGS = ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr'];

    private $cssPrinter;
    private $styleSheet;

    public function __construct(CssPrinter $cssPrinter, CssStyleSheet $styleSheet)
    {
        $this->cssPrinter = $cssPrinter;
        $this->styleSheet = $styleSheet;
    }

    public function print(HtmlContainer $container) : string
    {
        return $this->printChildren($container);
    }

    private function printChildren(HtmlContainer $container) : string
    {
        $result = '';

        foreach ($container->getChildren() as $child) {
            if ($child instanceof HtmlElement) {
                $printedChildren = $this->printChildren($child);
                $isSelfClosing = ($printedChildren === '') && \in_array($child->getTag(), self::VOID_TAGS);
                $result .= $this->printOpeningTag($child, $isSelfClosing);

                if (!$isSelfClosing) {
                    $result .= $printedChildren;
                    $result .= $this->printClosingTag($child);
                }
            } elseif ($child instanceof HtmlText) {
                $result .= $this->htmlEscape($child->getText());
            } else {
                throw new CleanerException('Doesn\'t know what to do with child "' . TypeUtils::getClass($child) . '"');
            }
        }

        return $result;
    }

    private function isBlockyElement(HtmlElement $element, CssSelector $selector) : bool
    {
        $style = clone $this->styleSheet->computeStyle($selector);
        $style->appendAll($child->getComputedStyle());
        $displayValue = $style->getDeclarationExpression(CssDeclaration::PROP_DISPLAY, '');

        return ($displayValue !== ''
            && $displayValue !== CssDeclaration::DISPLAY_INLINE
            && $displayValue !== CssDeclaration::DISPLAY_INLINE_BLOCK
        );
    }

    private function printOpeningTag(HtmlElement $element, bool $isSelfClosing) : string
    {
        $result = '<' . $element->getTag();

        foreach ($element->getAttributes() as $name => $value) {
            $result .= ' ' . $name . '="' . $this->htmlEscape($value) . '"';
        }

        $printedStyle = $this->cssPrinter->printStyle($element->getComputedStyle());

        if ($printedStyle !== '') {
            $result .= ' style="' . $printedStyle . '"';
        }

        if ($isSelfClosing) {
            $result .= ' /';
        }

        return $result . '>';
    }

    private function printClosingTag(HtmlElement $element) : string
    {
        return '</' . $element->getTag() . '>';
    }

    private function htmlEscape(string $text) : string
    {
        $text = htmlspecialchars($text, ENT_QUOTES | ENT_XHTML);
        return str_replace(\mb_chr(0xa0), '&nbsp;', $text);
    }
}
