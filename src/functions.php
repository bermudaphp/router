<?php

namespace Bermuda\Router;

/**
 * @param string $path
 * @param array $tokens
 * @return Path
 */
function path(string $path, array $tokens): Path
{
    return new Path($path, $tokens);
}
