<?php


namespace Bermuda\Router\Exception;


/**
 * Class RouteNotFoundException
 * @package Bermuda\Router\Exception
 */
class RouteNotFoundException extends RouterException
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
