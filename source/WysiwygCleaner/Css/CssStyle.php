<?php

namespace WysiwygCleaner\Css;

class CssStyle
{
    private $declarations = [];

    public function __construct()
    {
    }

    public function getDeclarations() : array
    {
        return $this->declarations;
    }

    public function hasDeclaration(string $property) : bool
    {
        return isset($this->declarations[\strtolower($property)]);
    }

    public function getDeclaration(string $property) : CssDeclaration
    {
        return $this->declarations[\strtolower($property)] ?? new CssDeclaration();
    }

    public function getDeclarationExpression(string $property, string $defaultExpression) : string
    {
        return $this->hasDeclaration($property) ? $this->getDeclaration($property)->getExpression() : $defaultExpression;
    }

    public function append(CssDeclaration $declaration)
    {
        $property = $declaration->getProperty();

        if (!isset($this->declarations[$property])
            || !$this->declarations[$property]->isImportant()
            || $declarations->isImportant()
        ) {
            $this->declarations[$property] = $declaration;
        }
    }

    public function appendAll(CssStyle $other)
    {
        foreach ($other->declarations as $declaration) {
            $this->append($declaration);
        }
    }

    public function extend(CssDeclaration $declaration)
    {
        if ($declaration->getExpression() !== CssDeclaration::EXPR_INHERIT) {
            $this->declarations[$declaration->getProperty()] = $declaration;
        }
    }

    public function extendAll(CssStyle $other)
    {
        foreach ($other->declarations as $declaration) {
            $this->extend($declaration);
        }
    }

    public function visuallyEquals(CssStyle $other)
    {
        foreach ($this->declarations as $declaration) {
            $property = $declaration->getProperty();

            if (!$other->hasDeclaration($property)
                || $this->declarations[$property]->getExpression() !== $other->declarations[$property]->getExpression()
            ) {
                return false;
            }
        }

        foreach ($other->declarations as $declaration) {
            if (!$this->hasDeclaration($declaration->getProperty())) {
                return false;
            }
        }

        return true;
    }

    // public function __clone()
    // {
    // }

    public function prettyDump() : string
    {
        return implode(
            '; ',
            array_map(
                function (CssDeclaration $declaration) {
                    return "{$declaration->getProperty()}: {$declaration->getExpression()}";
                },
                $this->declarations
            )
        );
    }
}
