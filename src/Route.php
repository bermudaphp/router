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
  
    public static $default_http_methods = [
        RequestMethodInterface::METHOD_GET,
        RequestMethodInterface::METHOD_POST,
        RequestMethodInterface::METHOD_PUT,
        RequestMethodInterface::METHOD_PATCH,
        RequestMethodInterface::METHOD_DELETE,
        RequestMethodInterface::METHOD_OPTIONS,
    ];
        
    public static $default_route_tokens = [
        'id' => '\d+',
        'action' => '(create|read|update|delete)',
        'optional' => '/?(.*)',
        'any' => '.*'
    ];

    public function __construct(
        string $name, string $path, 
        $handler, $methods = null,
        ?array $middleware = null,
        ?array $tokens = null
    )
    {
        $this->name = $name;
        $this->path = $path;
        $this->handler = [$handler];
        
        $this->setTokens($tokens);
        $this->setMethods($methods);
        $this->setMiddleware($middleware);
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
        return [
            'name' => $this->name,
            'handler' => $this->handler,
            'path' => $this->path,
            'attributes' => $this->attributes,
            'methods' => 'methods',
            'tokens' => 'tokens',
        ];
    }

    /**
     * @param array $attributes
     * @return self
     */
    final public function withAttributes(array $attributes): self
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
    final public function withPrefix(string $prefix): self
    {
        $route = clone $this;
        $route->path = $prefix . $this->path;
        
        return $route;
    }

    /**
     * @param array|string|null $methods
     * @return array|self
     */
    final public function methods($methods = null)
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
    final public function tokens(?array $tokens = null)
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
    final public function before($middleware): self
    {
        return (clone $this)->setBeforeMiddleware($middleware);
    }
    
     /**
     * @param mixed $middleware
     * @return self
     */
    final public function after($middleware): self
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
                
        return new static($data['name'], $data['path'], $data['handler'], $data['methods'] ?? static::$default_http_methods, $data['middleware'] ?? null, $data['tokens'] ?? static::$default_route_tokens);
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
                $this->setAfterMiddleware($middleware);
                return $this;
            }
            
            $after ?: $this->setAfterMiddleware($middleware['after']);
            $before ?: $this->setBeforeMiddleware($middleware['before']);
        }
        
        return $this;
    }
    
    private function setBeforeMiddleware($methods): self
    {
        array_unshift($this->handler, $middleware);
        return $this;
    }
}
