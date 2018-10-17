<?php

namespace WysiwygCleaner\Layout;

interface LayoutElement
{
    /**
     * @param string $indent
     *
     * @return string
     */
    public function dump(string $indent = '') : string;
}
