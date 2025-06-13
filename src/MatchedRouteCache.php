<?php

declare(strict_types=1);

namespace Bermuda\Router;

/**
 * MatchedRouteCache - Caching decorator for route matching operations
 *
 * Provides transparent caching specifically for expensive route matching operations.
 * This decorator wraps any Matcher implementation and caches only successful
 * route matches to improve performance of repeated HTTP requests.
 *
 * Cache Behavior:
 * - Only successful matches (non-null RouteRecord) are cached
 * - Failed matches are not cached to allow dynamic route discovery
 * - Memory-efficient with configurable size limits and LRU-style eviction
 *
 */
final class MatchedRouteCache implements Matcher
{
    /**
     * Cache for successful route matches indexed by request signature
     * @var array<string, RouteRecord>
     */
    private array $matchCache = [];

    /**
     * Current number of entries in match cache
     */
    private int $matchCacheSize = 0;

    /**
     * Maximum number of match results to cache before eviction
     */
    private int $maxMatchCacheSize;

    /**
     * Create caching decorator for Matcher
     *
     * @param Matcher $matcher Underlying Matcher implementation to wrap
     * @param int $maxMatchCacheSize Maximum number of successful matches to cache
     */
    public function __construct(
        public readonly Matcher $matcher,
        int $maxMatchCacheSize = 500
    ) {
        $this->maxMatchCacheSize = $maxMatchCacheSize;
    }

    /**
     * Match route with caching for successful matches
     *
     * @param RouteMap $routes Route collection to search
     * @param string $uri Request URI to match against routes
     * @param string $requestMethod HTTP method (GET, POST, etc.)
     * @return RouteRecord|null Matched route with parameters or null if no match
     */
    public function match(RouteMap $routes, string $uri, string $requestMethod): ?RouteRecord
    {
        $cacheKey = $this->createMatchCacheKey($uri, $requestMethod);

        if (isset($this->matchCache[$cacheKey])) {
            return $this->matchCache[$cacheKey];
        }

        $route = $this->matcher->match($routes, $uri, $requestMethod);

        if ($route !== null) {
            if ($this->matchCacheSize >= $this->maxMatchCacheSize) {
                $this->evictMatchCache();
            }

            $this->matchCache[$cacheKey] = $route;
            $this->matchCacheSize++;
        }

        return $route;
    }

    /**
     * Create cache key for match operations
     */
    private function createMatchCacheKey(string $uri, string $method): string
    {
        return $method . ':' . $uri;
    }

    /**
     * Evict oldest half of match cache entries when limit is reached
     */
    private function evictMatchCache(): void
    {
        $keysToRemove = array_slice(array_keys($this->matchCache), 0, $this->maxMatchCacheSize / 2);

        foreach ($keysToRemove as $key) {
            unset($this->matchCache[$key]);
            $this->matchCacheSize--;
        }
    }
}