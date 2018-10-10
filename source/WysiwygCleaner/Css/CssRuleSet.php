<?php

namespace WysiwygCleaner\Css;

class CssRuleSet
{
    private $rules = [];

    public function __construct()
    {
    }

    public function getRules() : array
    {
        return $this->rules;
    }

    public function hasRule(string $property) : bool
    {
        return isset($this->rules[\strtolower($property)]);
    }

    public function getRule(string $property) : CssRule
    {
        return $this->rules[\strtolower($property)] ?? new CssRule();
    }

    public function getRuleValue(string $property, string $defaultValue) : string
    {
        return $this->hasRule($property) ? $this->getRule($property)->getValue() : $defaultValue;
    }

    public function addRule(CssRule $rule)
    {
        $property = $rule->getProperty();

        if (!isset($this->rules[$property]) || !$this->rules[$property]->isImportant() || $rule->isImportant()) {
            $this->rules[$property] = $rule;
        }
    }

    public function mergeWith(CssRuleSet $other)
    {
        foreach ($other->rules as $rule) {
            $this->addRule($rule);
        }
    }

    public function concat(CssRuleSet $other) : CssRuleSet
    {
        $result = new static();

        foreach ($this->rules as $rule) {
            $result->addRule($rule);
        }

        foreach ($other->rules as $rule) {
            $result->addRule($rule);
        }

        return $result;
    }

    public function prettyDump() : string
    {
        return implode(
            '; ',
            array_map(
                function (CssRule $rule) {
                    return "{$rule->getProperty()}: {$rule->getValue()}";
                },
                $this->rules
            )
        );
    }
}
