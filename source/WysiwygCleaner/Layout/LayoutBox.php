<?php

namespace WysiwygCleaner\Layout;

use WysiwygCleaner\Html\HtmlElement;

class LayoutBox implements LayoutElement
{
    private $blockContext;
    private $htmlElement;
    private $children = [];

    public function __construct(bool $blockContext, $htmlElement = null)
    {
        $this->blockContext = $blockContext;
        $this->htmlElement = $htmlElement;
    }

    public function isBlock() : bool
    {
        return $this->blockContext;
    }

    public function isInline() : bool
    {
        return !$this->blockContext;
    }

    public function isAnonymous() : bool
    {
        return ($this->htmlElement === null);
    }

    public function getHtmlElement() : HtmlElement
    {
        return $this->htmlElement ?? new HtmlElement('');
    }

    public function getChildren() : array
    {
        return $this->children;
    }

    public function appendChild(LayoutElement $child)
    {
        $this->children[] = $child;
    }

    public function getBlockContainer() : LayoutBox
    {
        if (!$this->blockContext) {
            if (!empty($this->children)) {
                $this->wrapChildren(true);
            }

            $this->blockContext = true;
        } else if (!empty($this->children)) {
            $child = end($this->children);

            if (!($child instanceof LayoutBox) || !$child->blockContext) {
                $this->wrapChildren(true);
            }
        }

        return $this;
    }

    public function getInlineContainer() : LayoutBox
    {
        if (empty($this->children)) {
            return $this;
        }

        $child = end($this->children);

        if (!$this->blockContext || !($child instanceof LayoutBox) || !$child->blockContext) {
            return $this;
        }

        if (!$child->isAnonymous()) {
            $child = new LayoutBox(true);
            $this->children[] = $child;
        }

        return $child;
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

        $result = ($this->blockContext ? '#block' : '#inline') . '-box';

        if ($this->htmlElement !== null) {
            $result .= " : {$this->htmlElement->getTag()} { " . $this->htmlElement->getComputedStyle()->prettyDump() . ' }';
        }

        return trim("{$result}\n{$childrenDump}") . "\n";
    }

    private function wrapChildren(bool $blockContext)
    {
        $wrapper = new LayoutBox($blockContext);
        $wrapper->children = $this->children;
        $this->children = [$wrapper];
    }
}
