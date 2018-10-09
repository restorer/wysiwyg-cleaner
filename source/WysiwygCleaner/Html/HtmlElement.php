<?php

namespace WysiwygCleaner\Html;

use WysiwygCleaner\Css\CssRuleSet;

// Mutable
class HtmlElement implements HtmlNode, HtmlContainer
{
    private $tag;
    private $attributes = [];
    private $children = [];
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

    public function getChildren() : array
    {
        return $this->children;
    }

    public function appendChild(HtmlNode $child)
    {
        $this->children[] = $child;
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
        $childrenDump = implode(
            "\n",
            array_map(
                function (HtmlNode $child) {
                    return trim($child->prettyDump());
                },
                $this->children
            )
        );

        $childrenDump = implode(
            "\n",
            array_map(
                function (string $line) {
                    return "    {$line}";
                },
                explode("\n", $childrenDump)
            )
        );

        $result = "HtmlElement:{$this->tag}";

        if ($this->computedStyle !== null) {
            $result .= "({$this->computedStyle->prettyDump()})";
        }

        return trim("{$result}\n{$childrenDump}") . "\n";
    }
}
