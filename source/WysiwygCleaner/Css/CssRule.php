<?php

namespace WysiwygCleaner\Css;

class CssRule
{
    private $property = '';
    private $value = '';
    private $important = false;

    public function __construct(string $property, string $value, bool $important = false)
    {
        $this->property = $property;
        $this->value = $value;
        $this->important = $important;
    }

    public function getProperty() : string
    {
        return $this->property;
    }

    public function getValue() : string
    {
        return $this->value;
    }

    public function isImportant() : bool
    {
        return $this->important;
    }
}
