<?php

namespace WysiwygCleaner\Css;

class CssDeclaration
{
    const PROP_DISPLAY = 'display';

    const EXPR_INHERIT = 'inherit';

    const DISPLAY_NONE = 'none';
    const DISPLAY_INLINE = 'inline';

    /** @var string */
    private $property;

    /** @var string */
    private $expression;

    /** @var bool */
    private $important;

    /**
     * @param string $property
     * @param string $expression
     * @param bool $important
     */
    public function __construct(string $property, string $expression, bool $important = false)
    {
        $this->property = \strtolower($property);
        $this->expression = $expression;
        $this->important = $important;
    }

    /**
     * @return string
     */
    public function getProperty() : string
    {
        return $this->property;
    }

    /**
     * @return string
     */
    public function getExpression() : string
    {
        return $this->expression;
    }

    /**
     * @return bool
     */
    public function isImportant() : bool
    {
        return $this->important;
    }

    /**
     * @param CssDeclaration $other
     *
     * @return bool
     */
    public function equals(CssDeclaration $other) : bool
    {
        return ($this->expression === $other->expression);
    }

    /**
     * @return bool
     */
    public function isInternalDeclaration() : bool
    {
        return preg_match('/^var\(\-\-cleaner\-[^\)]+\)$/', $this->expression);
    }
}
