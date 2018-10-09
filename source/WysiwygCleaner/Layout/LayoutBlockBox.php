<?php

namespace WysiwygCleaner\Layout;

class LayoutBlockBox extends AbstractLayoutBox
{
    public function getBlockContainer() : LayoutBox
    {
        if (!empty($this->children) && !(end($this->children) instanceof LayoutBlockBox)) {
            $box = new LayoutBlockBox('', $this->computedStyle);
            $box->children = $this->children;
            $this->children = [$box];
        }

        return $this;
    }

    public function getInlineContainer() : LayoutBox
    {
        if (empty($this->children) || !(end($this->children) instanceof LayoutInlineBox)) {
            $this->children[] = new LayoutInlineBox('', $this->computedStyle);
        }

        return end($this->children);
    }
}
