<?php

namespace WysiwygCleaner\Html;

use WysiwygCleaner\CleanerUtils;
use WysiwygCleaner\Css\CssStyle;

class HtmlText implements HtmlNode
{
    /** @var string */
    private $text;

    /** @var CssStyle|null */
    private $computedStyle;

    /** @var bool */
    private $strippable;

    /**
     * @param string $text
     * @param CssStyle|null $computedStyle
     * @param bool $strippable
     */
    public function __construct(string $text, $computedStyle = null, bool $strippable = false)
    {
        $this->text = $text;

        if ($computedStyle !== null) {
            $this->computedStyle = $computedStyle;
        }

        $this->strippable = $strippable;
    }

    /**
     * @return string
     */
    public function getText() : string
    {
        return $this->text;
    }

    /**
     * @param string $text
     */
    public function setText(string $text)
    {
        $this->text = $text;
    }

    /**
     * @param string $text
     */
    public function prependText(string $text)
    {
        $this->text = $text . $this->text;
    }

    /**
     * @param string $text
     */
    public function appendText(string $text)
    {
        $this->text .= $text;
    }

    /**
     * @return string
     */
    public function getNodeType() : string
    {
        return HtmlNode::TYPE_TEXT;
    }

    /**
     * @return CssStyle
     */
    public function getComputedStyle() : CssStyle
    {
        return $this->computedStyle ?? new CssStyle();
    }

    /**
     * @param CssStyle $computedStyle
     */
    public function setComputedStyle(CssStyle $computedStyle)
    {
        $this->computedStyle = $computedStyle;
    }

    /**
     * @return bool
     */
    public function isStrippable() : bool
    {
        return $this->strippable;
    }

    /**
     * @param bool $strippable
     */
    public function setStrippable(bool $strippable)
    {
        $this->strippable = $strippable;
    }

    /**
     * @param string $indent
     *
     * @return string
     */
    public function dump(string $indent = '') : string
    {
        $result = '#text ' . CleanerUtils::dumpText($this->text);

        if ($this->computedStyle !== null) {
            $computedStyleDump = $this->computedStyle->dump();

            if ($computedStyleDump !== '') {
                $result .= ' { ' . $computedStyleDump . ' }';
            }
        }

        return $indent . $result . "\n";
    }
}
