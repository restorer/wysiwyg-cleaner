<?php

namespace WysiwygCleaner\Layout;

use WysiwygCleaner\Css\CssRuleSet;

abstract class AbstractLayoutBox implements LayoutElement, LayoutBox
{
    protected $tag;
    protected $computedStyle;
    protected $children = [];

    public function __construct(string $tag, CssRuleSet $computedStyle)
    {
        $this->tag = $tag;
        $this->computedStyle = $computedStyle;
    }

    public function getTag() : string
    {
        return $this->tag;
    }

    public function getComputedStyle() : CssRuleSet
    {
        return $this->computedStyle;
    }

    public function getChildren() : array
    {
        return $this->children;
    }

    public function appendChild(LayoutElement $child)
    {
        $this->children[] = $child;
    }

    public function prettyDump() : string
    {
        $childrenDump = implode(
            "\n",
            array_map(
                function (LayoutElement $child) {
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

        $result = (new \ReflectionClass($this))->getShortName();

        if ($this->tag !== '') {
            $result .= ":{$this->tag}";
            $computedStyleDump = $this->computedStyle->prettyDump();

            if ($computedStyleDump !== '') {
                $result .= "({$computedStyleDump})";
            }
        }

        return trim("{$result}\n{$childrenDump}") . "\n";
    }
}
