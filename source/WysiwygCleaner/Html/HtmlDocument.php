<?php

namespace WysiwygCleaner\Html;

// Mutable
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

        return trim("HtmlDocument\n{$childrenDump}") . "\n";
    }
}
