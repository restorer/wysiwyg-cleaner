<?php

namespace WysiwygCleaner\Css;

class CssDeclaration
{
    const PROP_DISPLAY = 'display';

    const DISPLAY_BLOCK = 'block';
    const DISPLAY_INLINE = 'inline';
    const DISPLAY_NONE = 'none';

    private $property = '';
    private $expression = '';
    private $important = false;

    public function __construct(string $property, string $expression, bool $important = false)
    {
        $this->property = \strtolower($property);
        $this->expression = $expression;
        $this->important = $important;
    }

    public function getProperty() : string
    {
        return $this->property;
    }

    public function getExpression() : string
    {
        return $this->expression;
    }

    public function isImportant() : bool
    {
        return $this->important;
    }
}
