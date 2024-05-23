<?php

namespace Bermuda\Router;

final class MatchedRoute
{
    public function __construct(
        public readonly string $name,
        public readonly string $path,
        public readonly mixed $handler,
        public readonly array $methods,
        public readonly array $params = []
    ) {
    }

    /**
     * @param array{
     *     name: string,
     *     path: string,
     *     methods: array<string>,
     *     handler: mixed,
     *     params: array<string|int>
     * } $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['name'],
            $data['path'],
            $data['handler'],
            $data['methods'],
            $data['params'] ?? []
        );
    }
}
