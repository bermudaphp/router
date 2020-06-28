<?php


namespace Bermuda\Router;


/**
 * Interface GeneratorInterface
 * @package Bermuda\Router
 */
interface GeneratorInterface
{
    /**
     * @param string $name name of route
     * @param array $attributes
     * @return string
     * @throws Exception\RouteNotFoundException
     * @throws Exception\GeneratorException
     */
    public function generate(string $name, array $attributes = []) : string ;
}
