<?php

namespace WysiwygCleaner\Html;

use WysiwygCleaner\Css\CssRuleSet;

class HtmlElement extends HtmlContainer implements HtmlNode
{
    private $tag;
    private $attributes = [];
    private $computedStyle;

    public function __construct(string $tag)
    {
        $this->tag = \strtolower($tag);
    }

    public function getTag() : string
    {
        return $this->tag;
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

    public function getComputedStyle() : CssRuleSet
    {
        return $this->computedStyle ?? new CssRuleSet();
    }

    public function setComputedStyle(CssRuleSet $computedStyle)
    {
        $this->computedStyle = $computedStyle;
    }

    public function prettyDump() : string
    {
        $result = $this->tag;

        if ($this->computedStyle !== null) {
            $result .= " { {$this->computedStyle->prettyDump()} }";
        }

        return trim($result . "\n" . parent::prettyDump()) . "\n";
    }
}
