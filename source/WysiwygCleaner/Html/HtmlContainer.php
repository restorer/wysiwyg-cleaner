<?php

namespace WysiwygCleaner\Html;

abstract class HtmlContainer
{
    protected $children = [];

    public function getChildren() : array
    {
        return $this->children;
    }

    public function appendChild(HtmlNode $child)
    {
        $this->children[] = $child;
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

        return implode(
            "\n",
            array_map(
                function (string $line) {
                    return "    {$line}";
                },
                explode("\n", $childrenDump)
            )
        );
    }
}
