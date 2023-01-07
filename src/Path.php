<?php

namespace Bermuda\Router;

class Path implements Stringable {
    public function __construct(
        private readonly string $path,
        private readonly array $tokens
    ) {
    }

    public function __toString()
    {
        $replace = [];
        $search = [];

        foreach ($this->tokens as $token => $v) {
            if (is_int($token)) {
                $replace[] = \Bermuda\Router\Attribute::wrap($v);
                $search[] = $v;
            } else {
                $replace[] = \Bermuda\Router\Attribute::wrap($token);
                $search[] = $token;
            }
        }

        return str_replace($search, $replace, $this->path);
    }

    public function getTokens(): array
    {
        return array_filter($this->tokens, static fn($v, $k): bool => !is_int($k));
    }
}
