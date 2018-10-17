<?php

namespace WysiwygCleaner\Html;

use WysiwygCleaner\CleanerUtils;

class HtmlDocument extends HtmlContainer
{
    /**
     * @param string $indent
     *
     * @return string
     */
    public function dump(string $indent = '') : string
    {
        return "{$indent}#document\n" . parent::dump($indent . CleanerUtils::INDENT);
    }
}
