<?php

namespace WysiwygCleaner\Layout;

class LayoutInlineBox extends AbstractLayoutBox
{
    public function getBlockContainer() : LayoutBox
    {
        if (empty($this->children) || !(end($this->children) instanceof LayoutBlockBox)) {
            $this->children[] = new LayoutBlockBox('', $this->computedStyle);
        }

        return end($this->children);
    }

    public function getInlineContainer() : LayoutBox
    {
        if (!empty($this->children)
            && !(end($this->children) instanceof LayoutInlineBox)
            && !(end($this->children) instanceof LayoutText)
        ) {
            $box = new LayoutInlineBox('', $this->computedStyle);
            $box->children = $this->children;
            $this->children = [$box];
        }

        return $this;
    }
}
