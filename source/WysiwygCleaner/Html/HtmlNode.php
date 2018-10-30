<?php

namespace WysiwygCleaner\Html;

use WysiwygCleaner\Css\CssStyle;

interface HtmlNode
{
    const TYPE_ELEMENT = 'element';
    const TYPE_TEXT = 'text';

    /**
     * @return string
     */
    public function getNodeType() : string;

    /**
     * @return CssStyle
     */
    public function getComputedStyle() : CssStyle;

    /**
     * @param CssStyle $computedStyle
     */
    public function setComputedStyle(CssStyle $computedStyle);

    /**
     * @return bool
     */
    public function isStrippable() : bool;

    /**
     * @param bool $strippable
     *
     * @return mixed
     */
    public function setStrippable(bool $strippable);

    /**
     * @param string $indent
     *
     * @return string
     */
    public function dump(string $indent = '') : string;
}
