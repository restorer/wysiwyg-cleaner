<?php

namespace WysiwygCleaner\Html;

use WysiwygCleaner\CleanerException;
use WysiwygCleaner\TypeUtils;

class HtmlParser
{
    public function __construct()
    {
    }

    public function parse(string $html, bool $shouldFailOnWarnings = false) : HtmlDocument
    {
        $prevUseErrors = \libxml_use_internal_errors(true);
        $sourceDom = new \DOMDocument();

        // TODO: https://stackoverflow.com/questions/39479994/php-domdocument-savehtml-breaks-format

        $success = $sourceDom->loadHTML(
            \mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'),
            LIBXML_BIGLINES | LIBXML_COMPACT | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        $errors = \libxml_get_errors();
        \libxml_clear_errors();
        \libxml_use_internal_errors($prevUseErrors);

        if (!$success || ($shouldFailOnWarnings && !empty($errors))) {
            $errors = \array_map(
                function ($error) {
                    return \trim($error->message) . " at {$error->line}:{$error->column}";
                },
                $errors
            );

            throw new CleanerException('Can\'t load html: ' . \implode(', ', $errors));
        }

        $destinationHtml = new HtmlDocument();
        $this->parseChildren($sourceDom, $destinationHtml);

        return $destinationHtml;
    }

    private function parseChildren(\DOMNode $sourceNode, HtmlContainer $destinationContainer)
    {
        $destinationChild = null;

        foreach ($sourceNode->childNodes as $sourceChild) {
            if ($sourceChild instanceof \DOMComment) {
                continue;
            }

            if ($sourceChild instanceof \DOMCharacterData) {
                if ($sourceChild->childNodes !== null && $sourceChild->childNodes->length) {
                    throw new CleanerException('"' . TypeUtils::getClass($sourceChild) . '" has non-empty child nodes');
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
                                '"' . TypeUtils::getClass($sourceChild) . '" instead of DOMAttr in attributes collection'
                            );
                        }

                        $destinationChild->setAttribute($attribute->name, $attribute->value);
                    }
                }

                $destinationContainer->appendChild($destinationChild);
                $this->parseChildren($sourceChild, $destinationChild);
                continue;
            }

            throw new CleanerException('Doesn\'t know what to do with "' . TypeUtils::getClass($sourceChild) . '"');
        }
    }
}
