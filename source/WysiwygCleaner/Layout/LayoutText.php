<?php

namespace WysiwygCleaner\Layout;

use WysiwygCleaner\Css\CssRuleSet;

class LayoutText implements LayoutElement
{
    private $text;
    private $paintStyle;

    public function __construct(string $text)
    {
        $this->text = $text;
    }

    public function getText() : string
    {
        return $this->text;
    }

    public function getPaintStyle() : CssRuleSet
    {
        return $this->paintStyle ?? new CssRuleSet();
    }

    public function setPaintStyle(CssRuleSet $paintStyle)
    {
        $this->paintStyle = $paintStyle;
    }

    public function prettyDump() : string
    {
        $result = '#inline-text "' . addcslashes($this->text, "\n\r\t\f\v\"\\") . "\"";

        if ($this->paintStyle !== null) {
            $result .= ' { ' . $this->paintStyle->prettyDump() . ' }';
        }

        return $result . "\n";
    }
}
