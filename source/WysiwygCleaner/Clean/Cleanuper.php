<?php

namespace WysiwygCleaner\Clean;

use WysiwygCleaner\Css\CssDeclaration;
use WysiwygCleaner\Css\CssStyle;
use WysiwygCleaner\Html\HtmlContainer;
use WysiwygCleaner\Html\HtmlElement;
use WysiwygCleaner\Html\HtmlNode;
use WysiwygCleaner\Html\HtmlText;
use WysiwygCleaner\TypeUtils;

class Cleanuper
{
    private $keepProperties;

    public function __construct(array $keepProperties)
    {
        $this->keepProperties = $keepProperties;
    }

    public function cleanup(HtmlContainer $container)
    {
        $children = [];

        foreach ($container->getChildren() as $node) {
            if ($node instanceof HtmlElement) {
                if ($this->getDisplayValue($node) === CssDeclaration::DISPLAY_NONE) {
                    continue;
                }

                if ($node->getTag() === HtmlElement::TAG_BR) {
                    $children[] = new HtmlElement(
                        HtmlElement::TAG_BR,
                        $node->getAttributes(),
                        $this->cleanupWhitespaceStyle($node->getComputedStyle())
                    );
                } else {
                    $this->cleanup($node);
                    $children[] = $node;
                }
            } elseif ($node instanceof HtmlText) {
                if (!preg_match('/^([ \t\n\r]*+)?(.*?)([ \t\n\r]*)$/', $node->getText(), $mt)) {
                    throw new ParserException('Internal error: regexp failed');
                }

                $whitespaceStyle = ($mt[1] !== '' || $mt[3] !== '')
                    ? $whitespaceStyle = $this->cleanupWhitespaceStyle($node->getComputedStyle())
                    : null;

                if ($mt[1] !== '') {
                    $children[] = new HtmlText(' ', $whitespaceStyle);
                }

                if ($mt[2] !== '') {
                    $children[] = new HtmlText($mt[2], $node->getComputedStyle());
                }

                if ($mt[3] !== '') {
                    $children[] = new HtmlText(' ', $whitespaceStyle);
                }
            } else {
                throw new ParserException('Doesn\'t know what to do with child "' . TypeUtils::getClass($child) . '"');
            }
        }

        $children = $this->stripWhitespaces($children);
        $children = $this->mergeTextNodes($children);

        $container->setChildren($children);
    }

    private function mergeTextNodes(array $children) : array
    {
        for ($i = 0; $i < \count($children) - 1;) {
            $child = $children[$i];
            $nextChild = $children[$i + 1];

            if (($child instanceof HtmlText)
                && ($nextChild instanceof HtmlText)
                && $child->getComputedStyle()->visuallyEquals($nextChild->getComputedStyle())
            ) {
                $child->appendText($nextChild->getText());
                array_splice($children, $i + 1, 1);
            } else {
                $i++;
            }
        }

        return $children;
    }

    private function stripWhitespaces(array $children) : array
    {
        while (!empty($children)) {
            $child = $children[0];

            if (!$this->isStrippableSpace($child) && !$this->isStrippableBr($child)) {
                break;
            }

            array_shift($children);
        }

        while (!empty($children)) {
            $child = $children[\count($children) - 1];

            if (!$this->isStrippableSpace($child) && !$this->isStrippableBr($child)) {
                break;
            }

            array_pop($children);
        }

        do {
            $hasChanges = false;

            for ($i = 0; $i < \count($children) - 1;) {
                $child = $children[$i];
                $nextChild = $children[$i + 1];

                if (($this->isStrippableSpace($child) && $this->isStrippableSpace($nextChild))
                    || ($this->isStrippableBr($child) && $this->isStrippableBr($nextChild))
                ) {
                    array_splice($children, $i + 1, 1);
                    $hasChanges = true;
                } else {
                    $i++;
                }
            }
        } while ($hasChanges);

        return $children;
    }

    private function cleanupWhitespaceStyle(CssStyle $style) : CssStyle
    {
        $result = new CssStyle();

        foreach ($style->getDeclarations() as $declaration) {
            if ($this->shouldKeepDeclaration($declaration)) {
                $result->append($declaration);
            }
        }

        return $result;
    }

    private function isStrippableSpace(HtmlNode $node) : bool
    {
        return (($node instanceof HtmlText)
            && ($node->getText() === ' ')
            && !$this->shouldKeepStyle($node->getComputedStyle(), CssDeclaration::DISPLAY_INLINE)
        );
    }

    private function isStrippableBr(HtmlNode $node) : bool
    {
        return (($node instanceof HtmlElement)
            && ($node->getTag() === HtmlElement::TAG_BR)
            && !$this->shouldKeepStyle($node->getComputedStyle(), CssDeclaration::DISPLAY_BLOCK)
        );
    }

    private function shouldKeepStyle(CssStyle $style, string $displayExpression) : bool
    {
        foreach ($style->getDeclarations() as $declaration) {
            $property = $declaration->getProperty();

            if ($property === CssDeclaration::PROP_DISPLAY || $declaration->getExpression() === $displayExpression) {
                continue;
            }

            foreach ($this->keepProperties as $regexp) {
                if (preg_match($regexp, $property)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function shouldKeepDeclaration(CssDeclaration $declaration) : bool
    {
        $property = $declaration->getProperty();

        if ($property === CssDeclaration::PROP_DISPLAY) {
            return true;
        }

        foreach ($this->keepProperties as $regexp) {
            if (preg_match($regexp, $property)) {
                return true;
            }
        }

        return false;
    }

    private function getDisplayValue(HtmlElement $element) : string
    {
        return \strtolower($element->getComputedStyle()->getDeclarationExpression(CssDeclaration::PROP_DISPLAY, ''));
    }
}
