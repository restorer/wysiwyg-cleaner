<?php

namespace WysiwygCleaner;

use WysiwygCleaner\Css\CssDeclaration;
use WysiwygCleaner\Css\CssStyle;
use WysiwygCleaner\Html\HtmlElement;
use WysiwygCleaner\Html\HtmlNode;

class CleanerUtils
{
    const INDENT = '    ';

    const NBSP_CHARACTER = "\xc2\xa0"; // equals to \mb_chr(0xa0, 'UTF-8')

    /**
     * @param $value
     *
     * @return string
     */
    public static function getClass($value) : string
    {
        return \is_object($value) ? \get_class($value) : \gettype($value);
    }

    /**
     * @param string $text
     *
     * @return string
     */
    public static function dumpText(string $text) : string
    {
        return '"' . addcslashes($text, "\n\r\t\f\v\"\\") . '"';
    }

    /**
     * @param string $display
     *
     * @return bool
     */
    public static function isInlineDisplay(string $display) : bool
    {
        return ($display === '' || $display === CssDeclaration::DISPLAY_INLINE);
    }

    /**
     * @param string $display
     *
     * @return bool
     */
    public static function isBlockyDisplay(string $display) : bool
    {
        // strpos() is used to cover "inline", "inline-block", "inline-flex", "inline-grid" and "inline-table"
        return ($display !== '' && strpos($display, CssDeclaration::DISPLAY_INLINE) === false);
    }

    /**
     * @param HtmlNode $node
     * @param string[] $keepWhitespacePropertiesRexegps
     *
     * @return bool
     */
    public static function isStrippableLineBreak(HtmlNode $node, array $keepWhitespacePropertiesRexegps) : bool
    {
        return (($node instanceof HtmlElement)
            && ($node->getTag() === HtmlElement::TAG_BR)
            && self::canStripWhitespaceStyle($node->getComputedStyle(), $keepWhitespacePropertiesRexegps)
        );
    }

    /**
     * @param CssStyle $style
     * @param string[] $keepWhitespacePropertiesRexegps
     *
     * @return bool
     */
    public static function canStripWhitespaceStyle(CssStyle $style, array $keepWhitespacePropertiesRexegps) : bool
    {
        if (!self::isInlineDisplay($style->getDisplay())) {
            return false;
        }

        foreach ($style->getDeclarations() as $property => $declaration) {
            if ($property === CssDeclaration::PROP_DISPLAY) {
                continue;
            }

            foreach ($keepWhitespacePropertiesRexegps as $regexp) {
                if (preg_match($regexp, $property)) {
                    return false;
                }
            }
        }

        return true;
    }
}
