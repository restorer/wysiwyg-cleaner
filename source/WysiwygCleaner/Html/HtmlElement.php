<?php

namespace WysiwygCleaner\Html;

use WysiwygCleaner\CleanerUtils;
use WysiwygCleaner\Css\CssStyle;

class HtmlElement extends HtmlContainer implements HtmlNode
{
    const TAG_BR = 'br';

    const ATTR_STYLE = 'style';
    const ATTR_CLASS = 'class';

    /** @var string */
    private $tag;

    /** @var array<string, string> */
    private $attributes = [];

    /** @var CssStyle|null */
    private $computedStyle;

    /** @var bool */
    private $strippable;

    /**
     * @param string $tag
     * @param null $attributes
     * @param CssStyle|null $computedStyle
     * @param bool $strippable
     */
    public function __construct(
        string $tag,
        $attributes = null,
        $computedStyle = null,
        bool $strippable = false
    ) {
        $this->tag = \strtolower($tag);

        if ($attributes !== null) {
            $this->attributes = $attributes;
        }

        if ($computedStyle !== null) {
            $this->computedStyle = $computedStyle;
        }

        $this->strippable = $strippable;
    }

    /**
     * @return string
     */
    public function getTag() : string
    {
        return $this->tag;
    }

    /**
     * @param string $tag
     */
    public function setTag(string $tag)
    {
        $this->tag = $tag;
    }

    /**
     * @return array<string, string>
     */
    public function getAttributes() : array
    {
        return $this->attributes;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasAttribute(string $name) : bool
    {
        return isset($this->attributes[\strtolower($name)]);
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function getAttribute(string $name) : string
    {
        return $this->attributes[\strtolower($name)] ?? '';
    }

    /**
     * @param string $name
     * @param string $value
     */
    public function setAttribute(string $name, string $value)
    {
        $this->attributes[\strtolower($name)] = $value;
    }

    /**
     * @param string $name
     */
    public function removeAttribute(string $name)
    {
        unset($this->attributes[\strtolower($name)]);
    }

    /**
     * @return string
     */
    public function getNodeType() : string
    {
        return HtmlNode::TYPE_ELEMENT;
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
        $result = ($this->tag === '' ? '?unknown' : $this->tag);

        if (!empty($this->attributes)) {
            $result .= '('
                . implode(
                    ' ',
                    array_map(
                        function (string $name, string $value) : string {
                            return $name . '=' . CleanerUtils::dumpText($value);
                        },
                        array_keys($this->attributes),
                        $this->attributes
                    )
                ) . ')';
        }

        if ($this->computedStyle !== null) {
            $computedStyleDump = $this->computedStyle->dump();

            if ($computedStyleDump !== '') {
                $result .= ' { ' . $computedStyleDump . ' }';
            }
        }

        return $indent . $result . "\n" . parent::dump($indent . CleanerUtils::INDENT);
    }
}
