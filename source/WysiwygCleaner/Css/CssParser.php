<?php

namespace WysiwygCleaner\Css;

use Sabberworm\CSS\OutputFormat as SabberwormCssOutputFormat;
use Sabberworm\CSS\Parser as SabberwormCssParser;
use Sabberworm\CSS\RuleSet\DeclarationBlock as SabberwormCssDeclarationBlock;
use Sabberworm\CSS\Value\Value as SabberwormCssValue;
use WysiwygCleaner\ParserException;
use WysiwygCleaner\TypeUtils;

class CssParser
{
    private $outputFormat;

    public function __construct()
    {
        $this->outputFormat = new SabberwormCssOutputFormat();
    }

    public function parseStyleSheet(string $styleSheet) : CssStyleSheet
    {
        $contents = (new SabberwormCssParser($styleSheet))->parse()->getContents();
        $styleSheet = new CssStyleSheet();

        foreach ($contents as $block) {
            if ($block instanceof SabberwormCssDeclarationBlock) {
                $ruleSet = $this->parseDeclarationBlock($block);

                foreach ($block->getSelectors() as $selector) {
                    $styleSheet->addDeclaration(new CssDeclaration(new CssSelector($selector->getSelector()), $ruleSet));
                }

                continue;
            }

            throw new ParserException('Doesn\'t know what to do with "' . TypeUtils::getClass($block) . '"');
        }

        return $styleSheet;
    }

    public function parseRuleSet(string $ruleSet) : CssRuleSet
    {
        $contents = (new SabberwormCssParser("ruleset { {$ruleSet} }"))->parse()->getContents();

        if (\count($contents) !== 1) {
            throw new ParserException(
                'Expecting 1 (one) contents item, but ' . \count($contents) . ' given'
            );
        }

        $block = $contents[0];

        if (!($block instanceof SabberwormCssDeclarationBlock)) {
            throw new ParserException(
                'Expecting DeclarationBlock, but "' . TypeUtils::getClass($declarationBlock) . '" given'
            );
        }

        $selectors = $block->getSelectors();

        if (\count($selectors) !== 1) {
            throw new ParserException(
                'Expecting 1 (one) declaration block selector, but ' . \count($selectors) . ' given'
            );
        }

        if ($selectors[0]->getSelector() !== 'ruleset') {
            throw new ParserException(
                'Expecting "ruleset" selector, but "' . $selectors[0]->getSelector() . '" given'
            );
        }

        return $this->parseDeclarationBlock($block);
    }

    private function parseDeclarationBlock(SabberwormCssDeclarationBlock $block) : CssRuleSet
    {
        $block->expandShorthands();
        $ruleSet = new CssRuleSet();

        foreach ($block->getRules() as $rule) {
            $ruleSet->addRule(new CssRule($rule->getRule(), $this->parseRuleValue($rule->getValue()), $rule->getIsImportant()));
        }

        return $ruleSet;
    }

    private function parseRuleValue($value) : string
    {
        return ($value instanceof SabberwormCssValue) ? $value->render($this->outputFormat) : (string)$value;
    }
}
