<?php

namespace WysiwygCleaner\Css;

class CssRule
{
    private $selectors;
    private $style;

    public function __construct(array $selectors, CssStyle $style)
    {
        $this->selectors = $selectors;
        $this->style = $style;
    }

    public function getSelectors() : array
    {
        return $this->selectors;
    }

    public function getStyle() : CssStyle
    {
        return $this->style;
    }
}
