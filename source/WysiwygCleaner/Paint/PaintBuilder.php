<?php

namespace WysiwygCleaner\Paint;

use WysiwygCleaner\Css\CssRule;
use WysiwygCleaner\Css\CssRuleSet;
use WysiwygCleaner\Css\CssUtils;
use WysiwygCleaner\Layout\LayoutBox;
use WysiwygCleaner\Layout\LayoutText;
use WysiwygCleaner\ParserException;
use WysiwygCleaner\TypeUtils;

class PaintBuilder
{
    private $flattenTags;
    private $keepAttributes;
    private $keepSpaceProperties;
    private $inlineStyle;

    public function __construct(array $flattenTags, array $keepAttributes, array $keepSpaceProperties)
    {
        $this->flattenTags = array_map('\strtolower', $flattenTags);
        $this->keepAttributes = array_map('\strtolower', $keepAttributes);
        $this->keepSpaceProperties = $keepSpaceProperties;

        $this->inlineStyle = new CssRuleSet();
        $this->inlineStyle->addRule(new CssRule(CssUtils::PROP_DISPLAY, CssUtils::DISPLAY_INLINE));
    }

    public function build(LayoutBox $container)
    {
        $this->paintStyles($container, $this->inlineStyle);
        // $this->buildPaintTree($container);
    }

    private function buildPaintTree(LayoutBox $container)
    {
    }

    private function paintStyles(LayoutBox $container, CssRuleSet $nearestPaintStyle)
    {
        if (!$container->isAnonymous()) {
            $nearestPaintStyle = $container->getHtmlElement()->getComputedStyle();
        } else {
            $nearestPaintStyle = $nearestPaintStyle->concat($this->inlineStyle);
        }

        foreach ($container->getChildren() as $child) {
            if ($child instanceof LayoutBox) {
                $this->paintStyles($child, $nearestPaintStyle);
            } elseif ($child instanceof LayoutText) {
                $child->setPaintStyle($nearestPaintStyle);
            } else {
                throw new ParserException('Doesn\'t know what to do with child "' . TypeUtils::getClass($child) . '"');
            }
        }
    }
}
