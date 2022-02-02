<?php

namespace Bermuda\Router\Exception;

class GeneratorException extends RouterException
{
    public static function create(string $id, string $routeName): self
    {
        return new GeneratorException("For route [$routeName] missing attribute [$id] missing.");
    }
}
