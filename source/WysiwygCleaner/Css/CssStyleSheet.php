<?php

namespace WysiwygCleaner\Css;

class CssStyleSheet
{
    private $styleMap = [];

    public function __construct()
    {
    }

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

    public function computeStyle(CssSelector $selector) : CssStyle
    {
        return $this->styleMap[$selector->getElementName()] ?? new CssStyle();
    }
}
