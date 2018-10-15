<?php

namespace WysiwygCleaner\Clean;

use WysiwygCleaner\Html\HtmlNode;
use WysiwygCleaner\Html\HtmlText;
use WysiwygCleaner\Html\HtmlElement;
use WysiwygCleaner\Html\HtmlContainer;
use WysiwygCleaner\Html\HtmlDocument;
use WysiwygCleaner\CleanerException;
use WysiwygCleaner\TypeUtils;

class Reconstructor
{
    private $reconstructTags;

    public function __construct(array $reconstructTags)
    {
        $this->reconstructTags = array_map('\strtolower', $reconstructTags);
    }

    public function reconstruct(HtmlDocument $document) : HtmlDocument
    {
        return $document;
    }
}
