<?php

namespace WysiwygCleaner\Css;

use Sabberworm\CSS\OutputFormat as SabberwormCssOutputFormat;
use Sabberworm\CSS\Parser as SabberwormCssParser;
use Sabberworm\CSS\Property\Selector as SabberwormSelector;
use Sabberworm\CSS\Rule\Rule as SabberwormCssRule;
use Sabberworm\CSS\RuleSet\DeclarationBlock as SabberwormCssDeclarationBlock;
use Sabberworm\CSS\Value\Value as SabberwormCssValue;
use WysiwygCleaner\CleanerException;
use WysiwygCleaner\CleanerUtils;

class CssParser
{
    /** @var SabberwormCssOutputFormat */
    private $outputFormat;

    /**
     */
    public function __construct()
    {
        $this->outputFormat = new SabberwormCssOutputFormat();
    }

    /**
     * @param string $styleSheet
     *
     * @return CssStyleSheet
     * @throws CleanerException
     */
    public function parseStyleSheet(string $styleSheet) : CssStyleSheet
    {
        $contents = (new SabberwormCssParser($styleSheet))->parse()->getContents();
        $result = new CssStyleSheet();

        foreach ($contents as $block) {
            if (!$block instanceof SabberwormCssDeclarationBlock) {
                throw new CleanerException('Doesn\'t know what to do with "' . CleanerUtils::getClass($block) . '"');
            }

            $result->append(
                new CssRule(
                    array_map(
                        function (SabberwormSelector $selector) : CssSelector {
                            return new CssSelector($selector->getSelector());
                        },
                        $block->getSelectors()
                    ),
                    $this->parseDeclarationBlock($block)
                )
            );
        }

        return $result;
    }

    /**
     * @param string $style
     *
     * @return CssStyle
     * @throws CleanerException
     */
    public function parseStyle(string $style) : CssStyle
    {
        $contents = (new SabberwormCssParser("style { {$style} }"))->parse()->getContents();

        if (\count($contents) !== 1) {
            throw new CleanerException(
                'Expecting 1 (one) contents item, but ' . \count($contents) . ' given'
            );
        }

        $block = $contents[0];

        if (!($block instanceof SabberwormCssDeclarationBlock)) {
            throw new CleanerException(
                'Expecting DeclarationBlock, but "' . CleanerUtils::getClass($block) . '" given'
            );
        }

        /** @var SabberwormSelector[] $selectors */
        $selectors = $block->getSelectors();

        if (\count($selectors) !== 1) {
            throw new CleanerException(
                'Expecting 1 (one) declaration block selector, but ' . \count($selectors) . ' given'
            );
        }

        if ($selectors[0]->getSelector() !== 'style') {
            throw new CleanerException(
                'Expecting "style" selector, but "' . $selectors[0]->getSelector() . '" given'
            );
        }

        return $this->parseDeclarationBlock($block);
    }

    /**
     * @param SabberwormCssDeclarationBlock $block
     *
     * @return CssStyle
     */
    private function parseDeclarationBlock(SabberwormCssDeclarationBlock $block) : CssStyle
    {
        $block->expandShorthands();
        $result = new CssStyle();

        foreach ($block->getRules() as $rule) {
            /** @var SabberwormCssRule $rule */

            $result->append(
                new CssDeclaration($rule->getRule(), $this->parseRuleValue($rule->getValue()), $rule->getIsImportant())
            );
        }

        return $result;
    }

    /**
     * @param $value
     *
     * @return string
     */
    private function parseRuleValue($value) : string
    {
        return ($value instanceof SabberwormCssValue) ? $value->render($this->outputFormat) : (string)$value;
    }
}
