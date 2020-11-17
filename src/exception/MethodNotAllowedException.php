<?php


namespace Bermuda\Router\Exception;


use Throwable;
use Bermuda\Router\RouteInterface;


/**
 * Class MethodNotAllowedException
 * @package Bermuda\Router\Exception
 */
final class MethodNotAllowedException extends RouterException
{
    private string $path;
    private string $requestMethod;
    private array $allowedMethods = [];

    public function __construct(string $path, string $requestMethod, array $allowedMethods)
    {
        $this->path = $path;
        $this->requestMethod = $requestMethod;
        parent::__construct(sprintf('The http method : %s for path: %s not allowed. Allows methods: %s.',
            $requestMethod, $path, implode(', ', $allowedMethods)
        ), 405);
    }

    /**
     * @param string $path
     * @param string $requestMethod
     * @param array $allowedMethods
     * @return static
     */
    public static function make(string $path, string $requestMethod, array $allowedMethods = []): self
    {
        return new self($path, $requestMethod, $allowedMethods);
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param array $methods
     */
    public function addAllowedMethods(array $methods): void
    {
        $this->allowedMethods = array_merge($this->allowedMethods, $methods);
        $this->message = sprintf('The http method : %s for path: %s not allowed. Allows methods: %s.',
            $this->requestMethod, $this->path, implode(', ', $this->allowedMethods)
        );
    }

    /**
     * @return string
     */
    public function getRequestMethod(): string
    {
        return $this->requestMethod;
    }

    /**
     * @return array
     */
    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }
}
