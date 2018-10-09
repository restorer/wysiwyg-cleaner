<?php

namespace WysiwygCleaner\Layout;

interface LayoutBox
{
    public function getChildren() : array;
    public function appendChild(LayoutElement $child);
    public function getBlockContainer() : LayoutBox;
    public function getInlineContainer() : LayoutBox;
}
