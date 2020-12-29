<?php

namespace Bermuda\Router;


/**
 * Class Route
 * @package Bermuda\Router
 */
class Route
{
    protected string $name;
    protected string $path;
    protected array $handler; 
    protected array $tokens = [];
    protected array $methods = [];
    protected array $attributes = [];
  
    public static default_http_methods = [
        RequestMethodInterface::METHOD_GET,
        RequestMethodInterface::METHOD_POST,
        RequestMethodInterface::METHOD_PUT,
        RequestMethodInterface::METHOD_PATCH,
        RequestMethodInterface::METHOD_DELETE,
        RequestMethodInterface::METHOD_OPTIONS,
    ];
        
    public static default_route_tokens = [
        'id' => '\d+',
        'action' => '(create|read|update|delete)',
        'optional' => '/?(.*)',
        'any' => '.*'
    ];

    public function __construct(
        string $name, string $path, 
        $handler, ?$methods = null, 
        ?array $middleware = null,
        ?array $tokens = null,
    )
    {
        $this->name = $name;
        $this->path = $path;
        $this->handler = [$handler];
        
        $this->setTokens($tokens);
        $this->setMethods($methods);
        
        if ($middleware != null)
        {
            !isset($middleware['after']) ?: $this->setAfterMiddleware($middleware['after']);
            !isset($middleware['before']) ?: $this->setBeforeMiddleware($middleware['before']);
        }
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param array $attributes
     * @return self
     */
    public function withAttributes(array $attributes): self
    {
        $route = clone $this;
        $route->attributes = $attributes;
        
        return $route;
    }

    /**
     * @return mixed
     */
    public function getHandler()
    {
        return count($this->handler) > 1 ? $this->handler : $this->handler[0];
    }

    /**
     * @param string $prefix
     * @return self
     */
    public function withPrefix(string $prefix): self
    {
        $route = clone $this;
        $route->path = $prefix . $this->path;
        
        return $route;
    }

    /**
     * @param array|string|null $methods
     * @return array|self
     */
    public function methods($methods = null)
    {
        if ($methods == null)
        {
            return $this->methods;
        }
     
        return (clone $this)->setMethods($methods);
    }

    /**
     * @return array|self
     */
    public function tokens(?array $tokens = null)
    {
        if ($tokens == null)
        {
            return $this->tokens;
        }
        
        return (clone $this)->setTokens($tokens);
    }
    
    /**
     * @param mixed $middleware
     * @return self
     */
    public function before($middleware): self
    {
        return (clone $this)->setBeforeMiddleware($middleware);
    }
    
     /**
     * @param mixed $middleware
     * @return self
     */
    public function after($middleware): self
    {
        return (clone $this)->setAfterMiddleware($middleware);
    }
    
    /**
     * @param array $data
     * @return self
     */
    public static function makeOf(array $data): self
    {
        foreach (['name', 'path', 'handler'] as $key)
        {
            if (!array_key_exists($key, $data))
            {
                throw new \InvalidArgumentException(sprintf('Missing %s $data[\'%s\']', __METHOD__, $key));
            }
        }
        
        return new static($data['name'], $data['path'], $data['handler'], $data['methods'] ?? static::default_http_methods, $data['middleware'] ?? null, $data['tokens'] ?? static::default_tokens);
    }
    
    protected function setTokens(?array $tokens): self
    {
        $this->tokens = array_merge($this->tokens, (array) $tokens);
        return $this;
    }
  
    protected function setMethods($methods): self
    {
        if (is_string($methods) && strpos($methods, '|') !== false)
        {
            $methods = explode('|', $methods);
        }
        
        $this->methods = array_map('strtoupper', (array) $methods);
     
        return $this;
    }
    
    protected function setAfterMiddleware($middleware): self
    {
        array_push($this->handler, $middleware);
        return $this;
    }
    
    protected function setBeforeMiddleware($methods): self
    {
        array_unshift($this->handler, $middleware);
        return $this;
    }
}
