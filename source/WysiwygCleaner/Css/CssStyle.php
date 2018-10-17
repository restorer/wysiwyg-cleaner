<?php

namespace WysiwygCleaner\Css;

/**
 * This class can be safely cloned
 */
class CssStyle
{
    /** @var array<string, CssDeclaration> */
    private $declarations = [];

    /**
     * @return CssDeclaration[]|array<string, CssDeclaration>
     */
    public function getDeclarations() : array
    {
        return $this->declarations;
    }

    /**
     * @param string $property
     *
     * @return bool
     */
    public function hasProperty(string $property) : bool
    {
        return isset($this->declarations[\strtolower($property)]);
    }

    /**
     * @param string $property
     *
     * @return CssDeclaration
     */
    public function getDeclaration(string $property) : CssDeclaration
    {
        return $this->declarations[\strtolower($property)] ?? new CssDeclaration($property, '');
    }

    /**
     * @param string $property
     * @param string $defaultExpression
     *
     * @return string
     */
    public function getExpression(string $property, string $defaultExpression = '') : string
    {
        return $this->hasProperty($property) ? $this->getDeclaration($property)->getExpression() : $defaultExpression;
    }

    /**
     * @return string
     */
    public function getDisplay() : string
    {
        return \strtolower($this->getExpression(CssDeclaration::PROP_DISPLAY, ''));
    }

    /**
     * @param CssDeclaration $declaration
     */
    public function append(CssDeclaration $declaration)
    {
        $property = $declaration->getProperty();

        if (!isset($this->declarations[$property])
            || $declaration->isImportant()
            || !$this->declarations[$property]->isImportant()
        ) {
            $this->declarations[$property] = $declaration;
        }
    }

    /**
     * @param CssStyle $other
     */
    public function appendAll(CssStyle $other)
    {
        foreach ($other->declarations as $declaration) {
            $this->append($declaration);
        }
    }

    /**
     * @param CssDeclaration $declaration
     */
    public function extend(CssDeclaration $declaration)
    {
        if ($declaration->getExpression() !== CssDeclaration::EXPR_INHERIT) {
            $this->declarations[$declaration->getProperty()] = $declaration;
        }
    }

    /**
     * @param CssStyle $other
     */
    public function extendAll(CssStyle $other)
    {
        foreach ($other->declarations as $declaration) {
            $this->extend($declaration);
        }
    }

    /**
     * @param CssStyle $other
     *
     * @return bool
     */
    public function visuallyEquals(CssStyle $other) : bool
    {
        foreach ($this->declarations as $property => $declaration) {
            if (!$other->hasProperty($property)
                || $this->declarations[$property]->getExpression() !== $other->declarations[$property]->getExpression()
            ) {
                return false;
            }
        }

        foreach ($other->declarations as $property => $declaration) {
            if (!$this->hasProperty($property)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param CssStyle $other
     *
     * @return int -1 when other cannot be extended from this, 0 when styles are equals and difference (> 0) when other can be extended from this
     */
    public function compareProperties(CssStyle $other) : int
    {
        foreach ($this->declarations as $property => $declaration) {
            if (!$other->hasProperty($property)) {
                return -1;
            }
        }

        $diff = 0;

        foreach ($other->declarations as $property => $declaration) {
            if (!$this->hasProperty($property)) {
                $diff++;
            }
        }

        return $diff;
    }

    /**
     * @return string
     */
    public function dump() : string
    {
        return implode(
            '; ',
            array_map(
                function (CssDeclaration $declaration) : string {
                    return $declaration->getProperty() . ': ' . $declaration->getExpression();
                },
                $this->declarations
            )
        );
    }
}
