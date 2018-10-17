<?php

namespace WysiwygCleaner\Css;

use WysiwygCleaner\Html\HtmlElement;

/**
 * TODO: this is incomplete stub class
 */
class CssSelector
{
    /** @var string */
    private $elementName;

    /**
     * @param string $elementName
     */
    public function __construct(string $elementName = '')
    {
        $this->elementName = \strtolower($elementName);
    }

    /**
     * @return string
     */
    public function getElementName() : string
    {
        return $this->elementName;
    }

    /**
     * @param CssSelector $other
     *
     * @return bool
     */
    public function equals(CssSelector $other) : bool
    {
        return $this->elementName === $other->elementName;
    }

    /**
     * @param CssSelector $other
     *
     * @return CssSelector
     */
    public function combine(CssSelector $other) : CssSelector
    {
        return $other;
    }

    /**
     * @param HtmlElement $element
     *
     * @return CssSelector
     */
    public static function forElement(HtmlElement $element) : CssSelector
    {
        return new CssSelector($element->getTag());
    }

    /**
     * @param string $tagName
     *
     * @return CssSelector
     */
    public static function forTagName(string $tagName) : CssSelector
    {
        return new CssSelector($tagName);
    }
}
