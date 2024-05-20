<?php

namespace Bermuda\Router\Exception;

final class RouteNotFoundException extends RouterException
{
    public function __construct(
      public readonly string $path,
      public readonly string $requestMethod,
      ?string $message = null
    ) {
        parent::__construct($message ?? sprintf('The route for the path: %s not found.', $path), 404);
    }
}
