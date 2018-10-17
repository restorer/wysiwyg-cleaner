<?php

namespace WysiwygCleaner\Layout;

use WysiwygCleaner\CleanerUtils;
use WysiwygCleaner\Css\CssStyle;

class LayoutText implements LayoutElement
{
    /** @var string */
    private $text;

    /** @var CssStyle */
    private $computedStyle;

    /**
     * @param string $text
     * @param CssStyle $computedStyle
     */
    public function __construct(string $text, CssStyle $computedStyle)
    {
        $this->text = $text;
        $this->computedStyle = $computedStyle;
    }

    /**
     * @return string
     */
    public function getText() : string
    {
        return $this->text;
    }

    /**
     * @return CssStyle
     */
    public function getComputedStyle() : CssStyle
    {
        return $this->computedStyle;
    }

    /**
     * @param string $indent
     *
     * @return string
     */
    public function dump(string $indent = '') : string
    {
        $result = '#inline-text ' . CleanerUtils::dumpText($this->text);
        $computedStyleDump = $this->getComputedStyle()->dump();

        if ($computedStyleDump !== '') {
            $result .= ' { ' . $computedStyleDump . ' }';
        }

        return $indent . $result . "\n";
    }
}
