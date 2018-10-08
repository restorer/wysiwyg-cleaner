<?php

namespace WysiwygCleaner;

class TypeUtils
{
    public static function getClass($value) : string
    {
        return \is_object($value) ? \get_class($value) : gettype($value);
    }
}
