<?php

namespace WysiwygCleaner\Css;

// TODO: support more complex selectors than just tag name
class CssStyleSheet
{
    private $declarations = [];

    public function getDeclarations() : array
    {
        return $this->declarations;
    }

    public function addDeclaration(CssDeclaration $declaration)
    {
        $selectorRepresentation = $declaration->getSelector()->getRepresentation();

        if (isset($this->declarations[$selectorRepresentation])) {
            $existingDeclaration = $this->declarations[$selectorRepresentation];

            foreach ($declaration->getRuleSet()->getRules() as $rule) {
                $existingDeclaration->getRuleSet()->addRule($rule);
            }
        } else {
            $this->declarations[$selectorRepresentation] = $declaration;
        }
    }

    // TODO: potentially, rules from more that one declaration can be returned
    public function findMatchedRuleSet(CssSelector $selector) : CssRuleSet
    {
        $selectorRepresentation = $declaration->getSelector()->getRepresentation();
        return isset($this->declarations[$selectorRepresentation]) ? $this->declarations[$selectorRepresentation]->getRuleSet() : null;
    }
}
