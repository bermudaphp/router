<?php

namespace Bermuda\Router;


/**
 * Interface RouterInterface
 * @package Bermuda\Router
 */
interface RouterInterface extends MatcherInterface, GeneratorInterface
{
    /**
     * @return Route[]
     */
    public function getRoutes(): array ;
}
