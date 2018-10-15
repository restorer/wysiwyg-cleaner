<?php

namespace WysiwygCleaner\Css;

class CssSelector
{
    private $elementName;

    public function __construct(string $elementName)
    {
        $this->elementName = \strtolower($elementName);
    }

    public function getElementName() : string
    {
        return $this->elementName;
    }

    public function equals(CssSelector $other) : bool
    {
        return $this->elementName === $other->elementName;
    }
}
