<?php

namespace WysiwygCleaner\Html;

abstract class HtmlContainer
{
    /** @var HtmlNode[] */
    protected $children = [];

    /**
     * @return HtmlNode[]
     */
    public function getChildren() : array
    {
        return $this->children;
    }

    /**
     * @param HtmlNode[] $children
     */
    public function setChildren(array $children)
    {
        $this->children = $children;
    }

    /**
     * @param HtmlNode $child
     */
    public function appendChild(HtmlNode $child)
    {
        $this->children[] = $child;
    }

    /**
     * @param string $indent
     *
     * @return string
     */
    public function dump(string $indent = '') : string
    {
        return implode(
            '',
            array_map(
                function (HtmlNode $child) use ($indent) : string {
                    return $child->dump($indent);
                },
                $this->children
            )
        );
    }
}
