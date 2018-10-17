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

class CssRenderer
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
     * @param CssStyle $style
     *
     * @return string
     * @throws CleanerException
     */
    public function renderStyle(CssStyle $style) : string
    {
        $declarations = [];

        foreach ($style->getDeclarations() as $property => $declaration) {
            if (!$declaration->isInternalDeclaration()) {
                $declarations[] = $property . ':' . $declaration->getExpression();
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

        $block->createShorthands();

        return implode(
            ' ',
            array_map(
                function (SabberwormCssRule $rule) : string {
                    return $rule->getRule() . ': ' . $this->renderRuleValue($rule->getValue()) . ';';
                },
                $block->getRules()
            )
        );
    }

    /**
     * @param $value
     *
     * @return string
     */
    private function renderRuleValue($value) : string
    {
        return ($value instanceof SabberwormCssValue) ? $value->render($this->outputFormat) : (string)$value;
    }
}
