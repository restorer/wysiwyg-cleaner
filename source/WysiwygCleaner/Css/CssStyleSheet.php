<?php

namespace WysiwygCleaner\Css;

/**
 * TODO: this is incomplete stub class
 */
class CssStyleSheet
{
    /** @var array<string, CssRule> */
    private $styleMap = [];

    /**
     * @param CssRule $rule
     */
    public function append(CssRule $rule)
    {
        foreach ($rule->getSelectors() as $selector) {
            $elementName = $selector->getElementName();

            if (isset($this->styleMap[$elementName])) {
                $this->styleMap[$elementName]->appendAll($rule->getStyle());
            } else {
                $this->styleMap[$elementName] = $rule->getStyle();
            }
        }
    }

    /**
     * @param CssSelector $selector
     *
     * @return CssStyle
     */
    public function resolveStyle(CssSelector $selector) : CssStyle
    {
        return $this->styleMap[$selector->getElementName()] ?? new CssStyle();
    }
}
