<?php

namespace WysiwygCleaner\Html;

class HtmlElement implements HtmlNode, HtmlContainer
{
    private $tag;
    private $attributes = [];
    private $children = [];

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
}
