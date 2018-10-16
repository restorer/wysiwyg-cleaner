<?php

namespace WysiwygCleaner\Css;

use WysiwygCleaner\Html\HtmlElement;

// TODO: this is incomplete stub class
class CssSelector
{
    private $elementName;

    public function __construct(string $elementName = '')
    {
        // TODO: this is incomplete stub function
        $this->elementName = \strtolower($elementName);
    }

    public function getElementName() : string
    {
        return $this->elementName;
    }

    public function equals(CssSelector $other) : bool
    {
        // TODO: this is incomplete stub function
        return $this->elementName === $other->elementName;
    }

    public function combine(CssSelector $other) : CssSelector
    {
        // TODO: this is incomplete stub function
        return $other;
    }

    public static function forElement(HtmlElement $element) : CssSelector
    {
        // TODO: this is incomplete stub function
        return new CssSelector($element->getTag());
    }

    public static function forTagName(string $tagName) : CssSelector
    {
        // TODO: this is incomplete stub function
        return new CssSelector($tagName);
    }
}
