<?php

namespace WysiwygCleaner\Html;

use WysiwygCleaner\Css\CssStyle;

class HtmlText implements HtmlNode
{
    private $text;
    private $computedStyle;

    public function __construct(string $text, $computedStyle = null)
    {
        $this->text = $text;

        if ($computedStyle !== null) {
            $this->computedStyle = $computedStyle;
        }
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

    public function getComputedStyle() : CssStyle
    {
        return $this->computedStyle ?? new CssStyle();
    }

    public function setComputedStyle(CssStyle $computedStyle)
    {
        $this->computedStyle = $computedStyle;
    }

    public function prettyDump() : string
    {
        $result = '#text "' . addcslashes($this->text, "\n\r\t\f\v\"\\") . '"';

        if ($this->computedStyle !== null) {
            $computedStyleDump = $this->computedStyle->prettyDump();

            if ($computedStyleDump !== '') {
                $result .= " { {$computedStyleDump} }";
            }
        }

        return "{$result}\n";
    }
}
