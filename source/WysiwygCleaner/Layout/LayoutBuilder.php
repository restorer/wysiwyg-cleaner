<?php

namespace WysiwygCleaner\Layout;

use WysiwygCleaner\CleanerException;
use WysiwygCleaner\Css\CssDeclaration;
use WysiwygCleaner\Html\HtmlContainer;
use WysiwygCleaner\Html\HtmlDocument;
use WysiwygCleaner\Html\HtmlElement;
use WysiwygCleaner\Html\HtmlText;
use WysiwygCleaner\TypeUtils;

class LayoutBuilder
{
    public function __construct()
    {
    }

    public function build(HtmlDocument $document) : LayoutBox
    {
        $box = $this->buildLayoutTree($document);

        if ($box->isInline()) {
            throw new CleanerException('Internal error: root box must have block context');
        }

        return $box;
    }

    private function buildLayoutTree(HtmlContainer $container) : LayoutBox
    {
        if ($container instanceof HtmlDocument) {
            $box = new LayoutBox(true);
        } elseif ($container instanceof HtmlElement) {
            $display = $this->getDisplayValue($container);

            if ($display === CssDeclaration::DISPLAY_NONE) {
                throw new CleanerException('Internal error: root node shouldn\'t have display "none"');
            }

            $box = new LayoutBox($display === CssDeclaration::DISPLAY_BLOCK, $container);
        } else {
            throw new CleanerException('Doesn\'t know what to do with container "' . TypeUtils::getClass($container) . '"');
        }

        foreach ($container->getChildren() as $child) {
            if ($child instanceof HtmlElement) {
                switch ($this->getDisplayValue($child)) {
                    case CssDeclaration::DISPLAY_NONE:
                        // Skip
                        break;

                    case CssDeclaration::DISPLAY_BLOCK:
                        $box->getBlockContainer()->appendChild($this->buildLayoutTree($child));
                        break;

                    default:
                        $box->getInlineContainer()->appendChild($this->buildLayoutTree($child));
                }
            } elseif ($child instanceof HtmlText) {
                $box->getInlineContainer()->appendChild(new LayoutText(
                    $child->getText(),
                    $child->getComputedStyle()
                ));
            } else {
                throw new CleanerException('Doesn\'t know what to do with child "' . TypeUtils::getClass($child) . '"');
            }
        }

        return $box;
    }

    private function getDisplayValue(HtmlElement $element) : string
    {
        return \strtolower($element->getComputedStyle()->getDeclarationExpression(CssDeclaration::PROP_DISPLAY, ''));
    }
}
