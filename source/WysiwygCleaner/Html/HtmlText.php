<?php

namespace WysiwygCleaner\Html;

// Mutable
class HtmlText implements HtmlNode
{
    private $text;

    public function __construct(string $text)
    {
        $this->text = $text;
    }

    public function getText() : string
    {
        return $this->text;
    }

    public function setText(string $text)
    {
        $this->text = $text;
    }

    public function appendText(string $text)
    {
        $this->text .= $text;
    }

    public function prettyDump() : string
    {
        return "HtmlText \"{$this->text}\"\n";
    }
}
