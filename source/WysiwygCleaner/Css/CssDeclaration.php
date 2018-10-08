<?php

namespace WysiwygCleaner\Css;

class CssDeclaration
{
    private $selector;
    private $ruleSet;

    public function __construct(CssSelector $selector, CssRuleSet $ruleSet)
    {
        $this->selector = $selector;
        $this->ruleSet = $ruleSet;
    }

    public function getSelector() : CssSelector
    {
        return $this->selector;
    }

    public function getRuleSet() : CssRuleSet
    {
        return $this->ruleSet;
    }
}
