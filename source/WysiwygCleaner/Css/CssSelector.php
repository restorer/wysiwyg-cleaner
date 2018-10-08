<?php

namespace WysiwygCleaner\Css;

// TODO: support something more complex than just tag name
class CssSelector
{
    private $representation;

    public function __construct(string $representation)
    {
        $this->representation = \strtolower($representation);
    }

    public function getRepresentation() : string
    {
        return $this->representation;
    }

    public function matches(CssSelector $other)
    {
        return $this->representation === $other->representation;
    }
}
