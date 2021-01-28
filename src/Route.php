<?php

namespace Bermuda\Router;

use Bermuda\Arrayable;
use Fig\Http\Message\RequestMethodInterface;

/**
 * Class Route
 * @package Bermuda\Router
 */
class Route implements Arrayable
{
    protected string $name;
    protected string $path;
    protected array $handler; 
    protected array $tokens = [];
    protected array $methods = [];
    protected array $attributes = [];
  
    public static $requestMethods = [
        RequestMethodInterface::METHOD_GET,
        RequestMethodInterface::METHOD_POST,
        RequestMethodInterface::METHOD_PUT,
        RequestMethodInterface::METHOD_PATCH,
        RequestMethodInterface::METHOD_DELETE,
        RequestMethodInterface::METHOD_OPTIONS,
    ];
        
    public static $routeTokens = [
        'id' => '\d+',
        'action' => '(create|read|update|delete)',
        'optional' => '/?(.*)',
        'any' => '.*'
    ];

    private function __construct(array $routeData)
    {
        $this->name = $routeData['name'];
        $this->path = $routeData['path'];
        $this->handler = [$routeData['handler']];
        
        $this->setTokens($routeData['tokens']);
        $this->setMethods($routeData['methods']);
        $this->setMiddleware($routeData['middleware'] ?? null);
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
     * @return array
     */
    public function toArray(): array
    {
        return get_object_vars($this);
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
     * @return self
     */
    public function middleware($middleware): self
    {
        return (clone $this)->setMiddleware($middleware);
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
        
        if (!isset($data['methods']))
        {
            $data['methods'] = self::$requestMethods;
        }
        
        if (!isset($data['tokens']))
        {
            $data['tokens'] = self::$routeTokens;
        }
                
        return new self($data);
    }
    
    private function setTokens(?array $tokens): self
    {
        $this->tokens = array_merge($this->tokens, (array) $tokens);
        return $this;
    }
  
    private function setMethods($methods): self
    {
        if (is_string($methods) && strpos($methods, '|') !== false)
        {
            $methods = explode('|', $methods);
        }
        
        $this->methods = array_map('strtoupper', (array) $methods);
     
        return $this;
    }
    
    private function setAfterMiddleware($middleware): self
    {
        array_push($this->handler, $middleware);
        return $this;
    }
    
    private function setMiddleware($middleware): self
    {
        if ($middleware != null)
        {
            if ($before = !isset($middleware['before']) && $after = !isset($middleware['after']))
            {
                $this->setBeforeMiddleware($middleware);
                return $this;
            }
            
            $after ?: $this->setAfterMiddleware($middleware['after']);
            $before ?: $this->setBeforeMiddleware($middleware['before']);
        }
        
        return $this;
    }
    
    private function setBeforeMiddleware($middleware): self
    {
        array_unshift($this->handler, $middleware);
        return $this;
    }
}
