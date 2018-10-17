<?php

namespace WysiwygCleaner\Html;

use WysiwygCleaner\CleanerUtils;
use WysiwygCleaner\Css\CssStyle;

class HtmlElement extends HtmlContainer implements HtmlNode
{
    const TAG_BR = 'br';

    const ATTR_STYLE = 'style';

    /** @var string */
    private $tag;

    /** @var array<string, string> */
    private $attributes = [];

    /** @var CssStyle|null */
    private $computedStyle;

    /**
     * @param string $tag
     * @param array<string, string>|null $attributes
     * @param CssStyle|null $computedStyle
     */
    public function __construct(string $tag, $attributes = null, $computedStyle = null)
    {
        $this->tag = \strtolower($tag);

        if ($attributes !== null) {
            $this->attributes = $attributes;
        }

        if ($computedStyle !== null) {
            $this->computedStyle = $computedStyle;
        }
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
     * @return CssStyle
     */
    public function getComputedStyle() : CssStyle
    {
        return $this->computedStyle ?? new CssStyle();
    }

    /**
     * @param CssStyle $computedStyle
     *
     * @return mixed|void
     */
    public function setComputedStyle(CssStyle $computedStyle)
    {
        $this->computedStyle = $computedStyle;
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
