<?php


namespace Lobster\Routing\Contracts;


use Lobster\Routing\Exceptions\GeneratorException;
use Lobster\Routing\Exceptions\RouteNotFoundException;


/**
 * Interface Generator
 * @package Lobster\Routing\Contracts
 */
interface Generator
{
    /**
     * @param string $name name of route
     * @param array $attributes
     * @return string
     * @throws RouteNotFoundException
     * @throws GeneratorException
     */
    public function generate(string $name, array $attributes = []) : string ;
}
