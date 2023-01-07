<?php

namespace Bermuda\Router;

class Path implements \Stringable {
    public function __construct(
        private string $path,
        private array $tokens
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

    /**
     * @param string $prefix
     * @return void
     */
    public function addPrefix(string $prefix): void
    {
        $this->path = $prefix . $this->path;
    }

    /**
     * @param array $tokens
     * @return void
     */
    public function mergeTokens(array $tokens): void
    {
        $this->tokens = array_merge($this->tokens, $tokens);
    }

    /**
     * @return array
     */
    public function getTokens(): array
    {
        return array_filter($this->tokens, static fn($v, $k): bool => !is_int($k), ARRAY_FILTER_USE_BOTH);
    }
}
