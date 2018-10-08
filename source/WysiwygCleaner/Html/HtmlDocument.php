<?php

namespace WysiwygCleaner\Html;

class HtmlDocument implements HtmlContainer
{
    private $children = [];

    public function __construct()
    {
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
