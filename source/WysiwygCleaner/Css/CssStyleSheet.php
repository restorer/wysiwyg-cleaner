<?php

namespace WysiwygCleaner\Css;

// TODO: support more complex selectors than just tag name
class CssStyleSheet
{
    private $declarations = [];

    public function __construct()
    {
    }

    public function getDeclarations() : array
    {
        return $this->declarations;
    }

    public function addDeclaration(CssDeclaration $declaration)
    {
        $selectorRepresentation = $declaration->getSelector()->getRepresentation();

        if (isset($this->declarations[$selectorRepresentation])) {
            $this->declarations[$selectorRepresentation]->getRuleSet()->mergeWith($declaration->getRuleSet()->getRules());
        } else {
            $this->declarations[$selectorRepresentation] = $declaration;
        }
    }

    // TODO: potentially, rules from more that one declaration can be returned
    public function computeStyle(CssSelector $selector) : CssRuleSet
    {
        $representation = $selector->getRepresentation();
        return isset($this->declarations[$representation]) ? $this->declarations[$representation]->getRuleSet() : (new CssRuleSet());
    }
}
