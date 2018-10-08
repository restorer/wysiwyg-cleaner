<?php

namespace WysiwygCleaner\Css;

class CssRuleSet
{
    private $rules = [];

    public function getRules() : array
    {
        return $this->rules;
    }

    public function addRule(CssRule $rule)
    {
        $property = $rule->getProperty();

        if (!isset($this->rules[$property]) || !$this->rules[$property]->isImportant() || $rule->isImportant()) {
            $this->rules[$property] = $rule;
        }
    }
}
