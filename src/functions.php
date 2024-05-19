<?php

namespace Bermuda\Router;

function normalize_path(string $path): string
{
    return preg_replace('!/+!', '/', replace_slashes("/$path"));
}

function replace_slashes(string $path): string
{
    return str_replace('\\', '/', $path);
}


