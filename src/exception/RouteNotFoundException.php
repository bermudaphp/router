<?php

namespace Bermuda\Router\Exception;

final class RouteNotFoundException extends RouterException
{
    private ?string $path = null;
    private ?string $name = null;
    
    public function __construct()
    {
        parent::__construct('', 404);
    }
    
    /**
     * @param string $path
     * @return $this
     */
    public function setPath(string $path): self
    {
        $this->path = $path;
        $this->message = sprintf('The route for the path: %s not found.', $path);
        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        $this->message = sprintf('Route with name: %s not found.', $name);
        return $this;
    }
    
    /**
     * @return null|string
     */
    public function getName():? string
    {
        return $this->name;
    }
    
    /**
     * @return null|string
     */
    public function getPath():? string
    {
        return $this->path;
    }
}
