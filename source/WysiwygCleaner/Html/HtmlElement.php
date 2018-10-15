<?php

namespace WysiwygCleaner\Html;

use WysiwygCleaner\Css\CssStyle;

class HtmlElement extends HtmlContainer implements HtmlNode
{
    const TAG_BR = 'br';

    const ATTR_STYLE = 'style';

    private $tag;
    private $attributes = [];
    private $computedStyle;

    public function __construct(string $tag, $attributes = null, $computedStyle = null)
    {
        $this->tag = \strtolower($tag);

        if ($attributes !== null) {
            $this->attributes = $attributes;
        }

        if ($computedStyle !== null) {
            $this->computedStyle = $computedStyle;
        }
    }

    public function getTag() : string
    {
        return $this->tag;
    }

    public function setTag(string $tag)
    {
        $this->tag = $tag;
    }

    public function getAttributes() : array
    {
        return $this->attributes;
    }

    public function hasAttribute(string $name) : bool
    {
        return isset($this->attributes[\strtolower($name)]);
    }

    public function getAttribute(string $name) : string
    {
        return $this->attributes[\strtolower($name)] ?? '';
    }

    public function setAttribute(string $name, string $value)
    {
        $this->attributes[\strtolower($name)] = $value;
    }

    public function getComputedStyle() : CssStyle
    {
        return $this->computedStyle ?? new CssStyle();
    }

    public function setComputedStyle(CssStyle $computedStyle)
    {
        $this->computedStyle = $computedStyle;
    }

    public function prettyDump() : string
    {
        $result = ($this->tag === '' ? '?unknown' : $this->tag);

        if ($this->computedStyle !== null) {
            $computedStyleDump = $this->computedStyle->prettyDump();

            if ($computedStyleDump !== '') {
                $result .= " { {$computedStyleDump} }";
            }
        }

        return trim($result . "\n" . parent::prettyDump()) . "\n";
    }
}
