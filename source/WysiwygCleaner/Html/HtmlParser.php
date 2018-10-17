<?php

namespace WysiwygCleaner\Html;

use WysiwygCleaner\CleanerException;
use WysiwygCleaner\CleanerUtils;

class HtmlParser
{
    /**
     * @param string $html
     * @param bool $shouldFailOnWarnings
     *
     * @return HtmlDocument
     * @throws CleanerException
     */
    public function parse(string $html, bool $shouldFailOnWarnings = false) : HtmlDocument
    {
        $prevUseErrors = \libxml_use_internal_errors(true);
        $sourceDom = new \DOMDocument();

        // https://stackoverflow.com/a/39480858

        $success = $sourceDom->loadHTML(
            '<div>' . \mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8') . '</div>',
            LIBXML_BIGLINES | LIBXML_COMPACT | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        $errors = \libxml_get_errors();
        \libxml_clear_errors();
        \libxml_use_internal_errors($prevUseErrors);

        if (!$success || ($shouldFailOnWarnings && !empty($errors))) {
            $errors = \array_map(
                function ($error) : string {
                    return \trim($error->message) . " at {$error->line}:{$error->column}";
                },
                $errors
            );

            throw new CleanerException('Can\'t load html: ' . \implode(', ', $errors));
        }

        $destinationHtml = new HtmlDocument();
        $this->parseChildren($sourceDom->documentElement, $destinationHtml);

        return $destinationHtml;
    }

    /**
     * @param \DOMNode $sourceNode
     * @param HtmlContainer $destinationContainer
     *
     * @throws CleanerException
     */
    private function parseChildren(\DOMNode $sourceNode, HtmlContainer $destinationContainer)
    {
        $destinationChild = null;

        foreach ($sourceNode->childNodes as $sourceChild) {
            if ($sourceChild instanceof \DOMComment) {
                continue;
            }

            if ($sourceChild instanceof \DOMCharacterData) {
                if ($sourceChild->childNodes !== null && $sourceChild->childNodes->length) {
                    throw new CleanerException('"' . CleanerUtils::getClass($sourceChild) . '" has non-empty child nodes');
                }

                if ($destinationChild instanceof HtmlText) {
                    $destinationChild->appendText($sourceChild->wholeText);
                } else {
                    $destinationChild = new HtmlText($sourceChild->wholeText);
                    $destinationContainer->appendChild($destinationChild);
                }

                continue;
            }

            if ($sourceChild instanceof \DOMElement) {
                $destinationChild = new HtmlElement($sourceChild->tagName);

                if ($sourceChild->attributes !== null) {
                    foreach ($sourceChild->attributes as $attribute) {
                        if (!($attribute instanceof \DOMAttr)) {
                            throw new CleanerException(
                                '"'
                                . CleanerUtils::getClass($sourceChild)
                                . '" instead of DOMAttr in attributes collection'
                            );
                        }

                        $destinationChild->setAttribute($attribute->name, $attribute->value);
                    }
                }

                $destinationContainer->appendChild($destinationChild);
                $this->parseChildren($sourceChild, $destinationChild);
                continue;
            }

            throw new CleanerException('Doesn\'t know what to do with "' . CleanerUtils::getClass($sourceChild) . '"');
        }
    }
}
