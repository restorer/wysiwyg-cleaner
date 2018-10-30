<?php

namespace WysiwygCleaner\Html;

use WysiwygCleaner\CleanerException;
use WysiwygCleaner\CleanerUtils;

class HtmlRenderer
{
    const VOID_TAGS = [
        'area',
        'base',
        'br',
        'col',
        'embed',
        'hr',
        'img',
        'input',
        'link',
        'meta',
        'param',
        'source',
        'track',
        'wbr',
    ];

    /**
     * @param HtmlContainer $container
     *
     * @return string
     * @throws CleanerException
     */
    public function render(HtmlContainer $container) : string
    {
        return $this->renderChildren($container);
    }

    /**
     * @param HtmlContainer $container
     *
     * @return string
     * @throws CleanerException
     */
    private function renderChildren(HtmlContainer $container) : string
    {
        $result = '';

        foreach ($container->getChildren() as $child) {
            if ($child instanceof HtmlElement) {
                $printedChildren = $this->renderChildren($child);
                $isSelfClosing = ($printedChildren === '') && \in_array($child->getTag(), self::VOID_TAGS, true);
                $result .= $this->renderOpeningTag($child, $isSelfClosing);

                if (!$isSelfClosing) {
                    $result .= $printedChildren;
                    $result .= $this->renderClosingTag($child);
                }
            } elseif ($child instanceof HtmlText) {
                $result .= $this->renderEscapedText($child->getText(), false);
            } else {
                throw new CleanerException(
                    'Doesn\'t know what to do with child "' . CleanerUtils::getClass($child) . '"'
                );
            }
        }

        return $result;
    }

    /**
     * @param HtmlElement $element
     * @param bool $isSelfClosing
     *
     * @return string
     */
    private function renderOpeningTag(HtmlElement $element, bool $isSelfClosing) : string
    {
        $result = '<' . $element->getTag();

        foreach ($element->getAttributes() as $name => $value) {
            $result .= ' ' . $name . '="' . $this->renderEscapedText($value, true) . '"';
        }

        if ($isSelfClosing) {
            $result .= ' /';
        }

        return $result . '>';
    }

    /**
     * @param HtmlElement $element
     *
     * @return string
     */
    private function renderClosingTag(HtmlElement $element) : string
    {
        return '</' . $element->getTag() . '>';
    }

    /**
     * @param string $text
     * @param bool $escapeQuotes
     *
     * @return string
     */
    private function renderEscapedText(string $text, bool $escapeQuotes) : string
    {
        // Should we change \mb_chr(8211, 'UTF-8') to "&ndash;"?
        // Should we change "«" and "»" to "&laquo;" and "&raquo;"?

        $text = htmlspecialchars($text, ($escapeQuotes ? ENT_QUOTES : 0) | ENT_XHTML);
        return str_replace(CleanerUtils::NBSP_CHARACTER, '&nbsp;', $text);
    }
}
