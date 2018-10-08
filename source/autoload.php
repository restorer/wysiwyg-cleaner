<?php

spl_autoload_register(
    function ($className) {
        if (preg_match('/^WysiwygCleaner\\\\/', $className)) {
            require __DIR__
                . DIRECTORY_SEPARATOR
                . str_replace(['\\', '_'], DIRECTORY_SEPARATOR, $className)
                . '.php';
        }
    },
    true,
    false
);
