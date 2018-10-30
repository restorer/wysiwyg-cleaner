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
     * @param string $defaultDisplay
     *
     * @return string
     */
    public function getDisplay(string $defaultDisplay = '') : string
    {
        return \strtolower($this->getExpression(CssDeclaration::PROP_DISPLAY, $defaultDisplay));
    }

    /**
     * @return bool
     */
    public function isInlineDisplay() : bool
    {
        return ($this->getDisplay(CssDeclaration::DISPLAY_INLINE) === CssDeclaration::DISPLAY_INLINE);
    }

    /**
     * @return bool
     */
    public function isBlockyDisplay() : bool
    {
        // strpos() is used to cover "inline", "inline-block", "inline-flex", "inline-grid" and "inline-table"
        return (strpos($this->getDisplay(CssDeclaration::DISPLAY_INLINE), CssDeclaration::DISPLAY_INLINE) === false);
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
     * "Other style can be extended from this" means that other style can be constructed by just adding new properties,
     * and not by replacing existing properties, or reverthig them back.
     *
     * For example this style is "color: #f00; font-weight: bold;". Than when other style is:
     * - "color: #f00; font-weight: bold;" - it equals, result = 0;
     * - "color: #f00; font-weight: bold; font-style: italic;" - it can be extended, result = 1;
     * - "color: #f00; font-weight: bold; background-color: #fff; font-size: smaller;" - it can be extended, result = 2;
     * - "font-weight: bold;" - it can NOT be extended (because "color: #f00;" should be reverted), result = -1;
     * - "color: #00f; font-weight: bold;" - it can NOT be extended (because "color: #00f;" replaces "color: #f00;"), result = -1.
     *
     * @param CssStyle $other
     *
     * @return int
     */
    public function compareTo(CssStyle $other) : int
    {
        foreach ($this->declarations as $property => $declaration) {
            if (!$other->hasProperty($property) || !$declaration->equals($other->declarations[$property])) {
                return -1;
            }
        }

        $result = 0;

        foreach ($other->declarations as $property => $declaration) {
            if (!$this->hasProperty($property)) {
                $result++;
            }
        }

        return $result;
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
