<?php

namespace WysiwygCleaner\Css;

class CssRule
{
    /** @var CssSelector[] */
    private $selectors;

    /** @var CssStyle */
    private $style;

    /**
     * @param CssSelector[] $selectors
     * @param CssStyle $style
     */
    public function __construct(array $selectors, CssStyle $style)
    {
        $this->selectors = $selectors;
        $this->style = $style;
    }

    /**
     * @return CssSelector[]
     */
    public function getSelectors() : array
    {
        return $this->selectors;
    }

    /**
     * @return CssStyle
     */
    public function getStyle() : CssStyle
    {
        return $this->style;
    }
}
