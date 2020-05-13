<?php


namespace Lobster\Routing;


use Lobster\Resolver\Contracts\Resolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Lobster\Resolver\ResolverInterface;


/**
 * Class Route
 * @package Lobster\Routing
 */
class Route implements Contracts\Route
{
    /**
     * @var mixed
     */
    private $handler;

    /**
     * @var string
     */
    private string $path;

    /**
     * @var string
     */
    private string $name;

    /**
     * @var array
     */
    private array $tokens = [];

    /**
     * @var array
     */
    private array $methods = [];

    /**
     * @var array
     */
    private array $attributes = [];

    /**
     * Route constructor.
     * @param string $name
     * @param string $path
     * @param mixed $handler
     * @param array $methods
     * @param array $tokens
     */
    public function __construct(
        string $name, string $path, $handler,
        array $methods = [], array $tokens = []
    )
    {
        $this->name = $name;
        $this->path = $path;
        $this->handler = $handler;
        $this->methods = $methods;
        $this->tokens = $tokens;
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
     * @return Route
     */
    public function withAttributes(array $attributes): Route
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
        return $this->handler;
    }

    /**
     * @param string $prefix
     * @return Route
     */
    public function addPrefix(string $prefix) : Route
    {
        $this->path = $prefix . $this->path;
        return $this;
    }

    /**
     * @param string $suffix
     * @return Route
     */
    public function addSuffix(string $suffix) : Route
    {
        $this->path .= $suffix;
        return $this;
    }

    /**
     * @return array
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * @param array|null $tokens
     * @return array
     */
    public function tokens(array $tokens = null): array
    {
        if($tokens !== null)
        {
            foreach ($tokens as $token => $v){
                $this->tokens[$token] = $v;
            }
        }

        return $this->tokens;
    }
}
