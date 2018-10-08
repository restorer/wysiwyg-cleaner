<?php

namespace WysiwygCleaner\Html;

interface HtmlContainer
{
    public function getChildren() : array;
    public function appendChild(HtmlNode $child);
}
