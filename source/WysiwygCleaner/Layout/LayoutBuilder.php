<?php

namespace WysiwygCleaner\Layout;

use WysiwygCleaner\CleanerException;
use WysiwygCleaner\CleanerUtils;
use WysiwygCleaner\Css\CssDeclaration;
use WysiwygCleaner\Html\HtmlContainer;
use WysiwygCleaner\Html\HtmlDocument;
use WysiwygCleaner\Html\HtmlElement;
use WysiwygCleaner\Html\HtmlText;

class LayoutBuilder
{
    /**
     * @param HtmlDocument $document
     *
     * @return LayoutBox
     * @throws CleanerException
     */
    public function build(HtmlDocument $document) : LayoutBox
    {
        $box = $this->buildLayoutTree($document);

        if ($box->isInline()) {
            throw new CleanerException('Internal error: root box must have block context');
        }

        return $box;
    }

    /**
     * @param HtmlContainer $container
     *
     * @return LayoutBox
     * @throws CleanerException
     */
    private function buildLayoutTree(HtmlContainer $container) : LayoutBox
    {
        if ($container instanceof HtmlDocument) {
            $box = new LayoutBox(true);
        } elseif ($container instanceof HtmlElement) {
            if ($container->getComputedStyle()->getDisplay() === CssDeclaration::DISPLAY_NONE) {
                throw new CleanerException('Internal error: root node shouldn\'t have display "none"');
            }

            $box = new LayoutBox($container->getComputedStyle()->isBlockyDisplay(), $container);
        } else {
            throw new CleanerException(
                'Doesn\'t know what to do with container "' . CleanerUtils::getClass($container) . '"'
            );
        }

        foreach ($container->getChildren() as $child) {
            if ($child instanceof HtmlElement) {
                if ($child->getComputedStyle()->getDisplay() === CssDeclaration::DISPLAY_NONE) {
                    continue;
                }

                if ($child->getComputedStyle()->isBlockyDisplay()) {
                    $box->getBlockContainer()->appendChild($this->buildLayoutTree($child));
                } else {
                    $box->getInlineContainer()->appendChild($this->buildLayoutTree($child));
                }
            } elseif ($child instanceof HtmlText) {
                $box->getInlineContainer()->appendChild(
                    new LayoutText(
                        $child->getText(),
                        $child->getComputedStyle()
                    )
                );
            } else {
                throw new CleanerException(
                    'Doesn\'t know what to do with child "' . CleanerUtils::getClass($child) . '"'
                );
            }
        }

        return $box;
    }
}
