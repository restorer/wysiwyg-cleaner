<?php

namespace WysiwygCleaner\Css;

use Sabberworm\CSS\OutputFormat as SabberwormCssOutputFormat;
use Sabberworm\CSS\Parser as SabberwormCssParser;
use Sabberworm\CSS\RuleSet\DeclarationBlock as SabberwormCssDeclarationBlock;
use Sabberworm\CSS\Rule\Rule as SabberwormCssRule;
use Sabberworm\CSS\Value\Value as SabberwormCssValue;
use WysiwygCleaner\CleanerException;
use WysiwygCleaner\TypeUtils;

class CssPrinter
{
    private $outputFormat;

    public function __construct()
    {
        $this->outputFormat = new SabberwormCssOutputFormat();
    }

    public function printStyle(CssStyle $style) : string
    {
        $declarations = [];

        foreach ($style->getDeclarations() as $declaration) {
            if (!$declaration->hasInternalExpression()) {
                $declarations[] = $declaration->getProperty() . ':' . $declaration->getExpression();
            }
        }

        if (empty($declarations)) {
            return '';
        }

        $contents = (new SabberwormCssParser('style { ' . implode(';', $declarations) . ' }'))->parse()->getContents();

        if (\count($contents) !== 1) {
            throw new CleanerException(
                'Expecting 1 (one) contents item, but ' . \count($contents) . ' given'
            );
        }

        $block = $contents[0];

        if (!($block instanceof SabberwormCssDeclarationBlock)) {
            throw new CleanerException(
                'Expecting DeclarationBlock, but "' . TypeUtils::getClass($declarationBlock) . '" given'
            );
        }

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

        $block->createShorthands();

        return implode(
            ' ',
            array_map(
                function (SabberwormCssRule $rule) {
                    return $rule->getRule() . ': ' . $this->printRuleValue($rule->getValue()) . ';';
                },
                $block->getRules()
            )
        );
        return $block->render($this->outputFormat);
    }

    private function printRuleValue($value) : string
    {
        return ($value instanceof SabberwormCssValue) ? $value->render($this->outputFormat) : (string)$value;
    }
}
