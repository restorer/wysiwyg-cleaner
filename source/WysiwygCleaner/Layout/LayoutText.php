<?php

namespace WysiwygCleaner\Layout;

class LayoutText implements LayoutElement
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

    public function prettyDump() : string
    {
        return "LayoutText \"{$this->text}\"\n";
    }
}
