<?php

namespace WysiwygCleaner\Html;

use WysiwygCleaner\Css\CssStyle;

interface HtmlNode
{
    /**
     * @return CssStyle
     */
    public function getComputedStyle() : CssStyle;

    /**
     * @param CssStyle $computedStyle
     *
     * @return mixed
     */
    public function setComputedStyle(CssStyle $computedStyle);

    /**
     * @param string $indent
     *
     * @return string
     */
    public function dump(string $indent = '') : string;
}
