<?php

namespace Bermuda\Router;

trait PathExtractor
{
    /**
     * Extract and normalize path from URI
     *
     * Handles various URI formats correctly:
     * - Regular paths: /path/to/resource
     * - Paths with query: /path?query=value
     * - Paths with fragments: /path#fragment
     * - Malformed paths: //path, ///path
     * - Encoded paths: /path%20with%20spaces
     * - Paths with backslashes: /path\to\resource
     *
     * @param string $uri Complete URI or path from HTTP request
     * @return string Normalized path starting with /
     */
    private function extractPath(string $uri): string
    {
        // Decode URI first to handle encoded characters
        $uri = urldecode($uri);

        // Handle empty or root cases
        if (empty($uri) || $uri === '/') {
            return '/';
        }

        // Handle paths that start with // - these are problematic for parse_url
        // as it treats them as scheme-relative URLs (//host/path)
        if (str_starts_with($uri, '//')) {
            return $this->extractPathManually($uri);
        }

        // Try standard URL parsing for normal cases
        $parsedPath = parse_url($uri, PHP_URL_PATH);

        // If parse_url succeeds, use the result
        if ($parsedPath !== null && $parsedPath !== false) {
            return $this->normalizePath($parsedPath);
        }

        // Fallback to manual extraction
        return $this->extractPathManually($uri);
    }

    /**
     * Manually extract path from URI when parse_url is not suitable
     *
     * @param string $uri URI to extract path from
     * @return string Normalized path
     */
    private function extractPathManually(string $uri): string
    {
        $path = $uri;

        // Remove query string if present
        if (($queryPos = strpos($path, '?')) !== false) {
            $path = substr($path, 0, $queryPos);
        }

        // Remove fragment if present
        if (($fragmentPos = strpos($path, '#')) !== false) {
            $path = substr($path, 0, $fragmentPos);
        }

        // Handle empty path after extraction
        if (empty($path)) {
            return '/';
        }

        return $this->normalizePath($path);
    }

    /**
     * Normalize path string
     *
     * @param string $path Raw path to normalize
     * @return string Normalized path
     */
    private function normalizePath(string $path): string
    {
        // Replace backslashes with forward slashes
        $path = str_replace('\\', '/', $path);

        // Collapse multiple consecutive slashes into single slash
        $path = preg_replace('#/+#', '/', $path);

        // Ensure path starts with /
        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        // Remove trailing slash except for root
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        return $path;
    }
}