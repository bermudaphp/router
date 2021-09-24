<?php

namespace Bermuda\Router;

interface RouterInterface extends MatcherInterface, GeneratorInterface
{
    /**
     * @return Route[]
     */
    public function getRoutes(): array ;
}
