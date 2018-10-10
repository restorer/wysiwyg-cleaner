<?php

namespace WysiwygCleaner\Html;

class HtmlDocument extends HtmlContainer
{
    public function __construct()
    {
    }

    public function prettyDump() : string
    {
        return trim("#document\n" . parent::prettyDump()) . "\n";
    }
}
