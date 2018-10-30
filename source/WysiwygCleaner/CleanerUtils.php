<?php

namespace WysiwygCleaner;

use WysiwygCleaner\Css\CssStyle;

class CleanerUtils
{
    const INDENT = '    ';
    const NBSP_CHARACTER = "\xc2\xa0"; // equals to \mb_chr(0xa0, 'UTF-8')
    const BOM_CHARACTER = "\xef\xbb\xbf"; // equals to \mb_chr(0xfeff, 'UTF-8')

    /**
     * @param $value
     *
     * @return string
     */
    public static function getClass($value) : string
    {
        return \is_object($value) ? \get_class($value) : \gettype($value);
    }

    /**
     * @param string $text
     *
     * @return string
     */
    public static function dumpText(string $text) : string
    {
        return '"' . addcslashes($text, "\n\r\t\f\v\"\\") . '"';
    }

    /**
     * @param array[] $rules
     * @param array $values
     *
     * @return bool
     * @throws CleanerException
     */
    public static function matchRules(array $rules, array $values) : bool
    {
        foreach ($rules as $rule) {
            $count = \count($rule);

            if ($count === 0) {
                throw new CleanerException("Doesn't know what to do with empty rule");
            }

            $isMatched = true;

            foreach ($values as $key => $value) {
                if (\is_numeric($key)) {
                    $key++;
                }

                if (isset($rule[$key]) && !self::matchRuleValue($rule[$key], $value)) {
                    $isMatched = false;
                    break;
                }
            }

            if ($isMatched) {
                return $rule[0];
            }
        }

        throw new CleanerException("Rules doesn't have default rule");
    }

    /**
     * @param array $rules
     * @param CssStyle $style
     * @param string $tag
     *
     * @return CssStyle
     * @throws CleanerException
     */
    public static function cleanupStyle(array $rules, CssStyle $style, string $tag = '') : CssStyle
    {
        $cleanedStyle = new CssStyle();
        $isInline = $style->isInlineDisplay();

        foreach ($style->getDeclarations() as $property => $declaration) {
            if (self::matchRules(
                $rules,
                [$property, $declaration->getExpression(), 'tag' => $tag, 'inline' => $isInline]
            )) {
                $cleanedStyle->append($declaration);
            }
        }

        return $cleanedStyle;
    }

    /**
     * @param mixed $ruleValue
     * @param mixed $value
     *
     * @return bool
     */
    private static function matchRuleValue($ruleValue, $value) : bool
    {
        if (\is_string($ruleValue)
            && !empty($ruleValue)
            && (strpos($ruleValue, '/') === 0 || strpos($ruleValue, '@') === 0)
        ) {
            return preg_match($ruleValue, $value);
        }

        return $ruleValue === $value;
    }
}
