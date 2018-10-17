<?php

namespace WysiwygCleaner\Layout;

use WysiwygCleaner\CleanerUtils;
use WysiwygCleaner\Html\HtmlElement;

class LayoutBox implements LayoutElement
{
    /** @var bool */
    private $blockContext;

    /** @var HtmlElement|null */
    private $htmlElement;

    /** @var LayoutElement[] */
    private $children = [];

    /**
     * @param bool $blockContext
     * @param null $htmlElement
     */
    public function __construct(bool $blockContext, $htmlElement = null)
    {
        $this->blockContext = $blockContext;
        $this->htmlElement = $htmlElement;
    }

    /**
     * @return bool
     */
    public function isBlock() : bool
    {
        return $this->blockContext;
    }

    /**
     * @return bool
     */
    public function isInline() : bool
    {
        return !$this->blockContext;
    }

    /**
     * @return bool
     */
    public function isAnonymous() : bool
    {
        return ($this->htmlElement === null);
    }

    /**
     * @return HtmlElement
     */
    public function getHtmlElement() : HtmlElement
    {
        return $this->htmlElement ?? new HtmlElement('');
    }

    /**
     * @return LayoutElement[]
     */
    public function getChildren() : array
    {
        return $this->children;
    }

    /**
     * @param LayoutElement $child
     */
    public function appendChild(LayoutElement $child)
    {
        $this->children[] = $child;
    }

    /**
     * @return LayoutBox
     */
    public function getBlockContainer() : LayoutBox
    {
        if (!$this->blockContext) {
            /** @noinspection NotOptimalIfConditionsInspection */
            if (!empty($this->children)) {
                $this->wrapChildren(true);
            }

            $this->blockContext = true;
        } elseif (!empty($this->children)) {
            $child = end($this->children);

            if (!($child instanceof self) || !$child->blockContext) {
                $this->wrapChildren(true);
            }
        }

        return $this;
    }

    /**
     * @return LayoutBox
     */
    public function getInlineContainer() : LayoutBox
    {
        if (empty($this->children)) {
            return $this;
        }

        $child = end($this->children);

        if (!$this->blockContext || !($child instanceof self) || !$child->blockContext) {
            return $this;
        }

        if (!$child->isAnonymous()) {
            $child = new LayoutBox(true);
            $this->children[] = $child;
        }

        return $child;
    }

    /**
     * @param string $indent
     *
     * @return string
     */
    public function dump(string $indent = '') : string
    {
        $result = ($this->blockContext ? '#block' : '#inline') . '-box';

        if ($this->htmlElement !== null) {
            $result .= ' : ' . $this->htmlElement->getTag();
            $computedStyleDump = $this->htmlElement->getComputedStyle()->dump();

            if ($computedStyleDump !== '') {
                $result .= ' { ' . $computedStyleDump . ' }';
            }
        }

        return $indent
            . $result
            . "\n"
            . implode(
                '',
                array_map(
                    function (LayoutElement $child) use ($indent) : string {
                        return $child->dump($indent . CleanerUtils::INDENT);
                    },
                    $this->children
                )
            );
    }

    /**
     * @param bool $blockContext
     */
    private function wrapChildren(bool $blockContext)
    {
        $wrapper = new LayoutBox($blockContext);
        $wrapper->children = $this->children;
        $this->children = [$wrapper];
    }
}
