<?php

namespace Bermuda\Router\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS|\Attribute::TARGET_METHOD)] class Route
{
    /**
     * @var string[]
     */
    public readonly array $methods;

    public function __construct(
        public readonly string $name,
        public readonly string $path,
        array|string $methods = [
            'GET', 'POST', 'PUT',
            'PATCH', 'DELETE', 'HEAD',
            'OPTIONS'
        ],
        /**
         * @var string[]|null
         */
        public readonly ?array $middleware = null,
        public readonly ?string $group = null,
        public readonly ?int $priority = 0,
        public readonly ?array $defaults = null,
    ) {
        if (is_string($methods)) {
            if (str_contains($methods, '|',)) {
                $methods = explode('|', $methods);
            } else $methods = [$methods];
        }

        $this->methods = $methods;
    }
}
