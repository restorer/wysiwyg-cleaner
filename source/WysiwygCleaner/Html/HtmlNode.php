<?php

namespace WysiwygCleaner\Html;

use WysiwygCleaner\Css\CssStyle;

interface HtmlNode
{
    public function getComputedStyle() : CssStyle;
    public function setComputedStyle(CssStyle $computedStyle);
    public function prettyDump() : string;
}
