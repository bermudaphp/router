<?php


namespace Lobster\Routing\Exceptions;


/**
 * Class RouteNotFoundException
 * @package Lobster\Routing\Exceptions
 */
class RouteNotFoundException extends Exception
{
    /**
     * @param string $path
     * @return $this
     */
    public function setPath(string $path) : self
    {
        $this->message = sprintf('The route for the path: %s not found.', $path);
        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name) : self
    {
        $this->message = sprintf('Route with name: %s not found.', $name);
        return $this;
    }
}
