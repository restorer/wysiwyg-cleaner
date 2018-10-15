<?php

namespace WysiwygCleaner\Layout;

use WysiwygCleaner\Css\CssStyle;

class LayoutText implements LayoutElement
{
    private $text;
    private $computedStyle;

    public function __construct(string $text, CssStyle $computedStyle)
    {
        $this->text = $text;
        $this->computedStyle = $computedStyle;
    }

    public function getText() : string
    {
        return $this->text;
    }

    public function getComputedStyle() : CssStyle
    {
        return $this->computedStyle;
    }

    public function prettyDump() : string
    {
        $result = '#inline-text "' . addcslashes($this->text, "\n\r\t\f\v\"\\") . '"';
        $computedStyleDump = $this->htmlElement->getComputedStyle()->prettyDump();

        if ($computedStyleDump !== '') {
            $result .= " { {$computedStyleDump} }";
        }

        return "{$result}\n";
    }
}
