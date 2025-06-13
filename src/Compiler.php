<?php

declare(strict_types=1);

namespace Bermuda\Router;

/**
 * Route Pattern Compiler
 *
 * Compiles route patterns with square bracket parameter syntax into regular expressions
 * for efficient URL matching and parameter extraction. This compiler specifically handles
 * the square bracket syntax: [param], [?param], [param:pattern], [?param:pattern].
 *
 * Key Features:
 * - Parameter syntax: [name] for required, [?name] for optional parameters
 * - Inline patterns: [name:regex] to define validation patterns directly in routes
 * - Token system: Global patterns that can be applied to parameter names
 * - Default patterns: Fallback patterns for parameters without specific tokens
 * - URL generation: Reverse compilation from patterns to URLs with parameter substitution
 * - Optional handling: Smart handling of optional parameters with proper slash management
 * - Parameter validation: Ensures parameter names follow PHP variable naming conventions
 *
 * Supported Parameter Formats:
 * - Required: [id], [slug], [name]
 * - Optional: [?id], [?category], [?format]
 * - With patterns: [id:\d+], [slug:[a-z0-9-]+]
 * - Optional with patterns: [?format:json|xml], [?page:\d+]
 *
 * Parameter Name Validation:
 * - Must start with a letter or underscore
 * - Can contain letters, numbers, and underscores
 * - Cannot start with numbers
 * - Cannot contain special characters like hyphens, dots, or spaces
 * - Must follow PHP variable naming conventions
 *
 * @package Bermuda\Router
 */
class Compiler implements CompilerInterface
{
    /**
     * Collection of parameter patterns indexed by parameter name.
     *
     * These patterns are applied to parameters based on their names when no
     * inline pattern is specified. The patterns are merged with DEFAULT_PATTERNS
     * from the CompilerInterface, with custom patterns taking precedence.
     *
     * @var array<string, string> Parameter name => regex pattern mapping
     */
    private(set) array $patterns = [];

    /**
     * Default pattern used when no specific pattern is found.
     *
     * This pattern is used as a fallback when:
     * 1. No inline pattern specified: [name] instead of [name:pattern]
     * 2. No token pattern defined for the parameter name
     * 3. Parameter name not found in DEFAULT_PATTERNS
     *
     * Default value '[^\/]+' matches any character except forward slash,
     * which is appropriate for URL segments.
     *
     * @var string Regex pattern without delimiters
     */
    private(set) string $defaultPattern = '[^\/]+';

    /**
     * Initializes the compiler with custom patterns.
     *
     * This constructor accepts custom patterns that will be used in place of the corresponding patterns
     * defined in CompilerInterface::DEFAULT_PATTERNS. If a custom pattern for a specific parameter is not provided,
     * the default pattern will be used.
     *
     * @param array<string, string> $patterns Custom parameter patterns (name => regex)
     * @see CompilerInterface::DEFAULT_PATTERNS
     *
     * @example
     * $compiler = new Compiler([
     *     'id'   => '\d+',             // For parameter 'id', uses '\d+' instead of the default pattern.
     *     'uuid' => '[a-f0-9-]{36}',     // For parameter 'uuid', uses the custom pattern.
     *     'slug' => '[a-z0-9-_]+'        // For parameter 'slug', uses the custom pattern.
     * ]);
     */
    public function __construct(array $patterns = [])
    {
        $this->patterns = $patterns;
    }

    /**
     * Add or update parameter patterns.
     *
     * Merges new patterns with existing ones. New patterns with the same
     * parameter names will override existing patterns.
     *
     * @param array<string, string> $patterns Parameter patterns to add/update
     * @return self Fluent interface for method chaining
     *
     * @example
     * $compiler->setPatterns([
     *     'date' => '\d{4}-\d{2}-\d{2}',   // Add date pattern
     *     'email' => '[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}'
     * ]);
     */
    public function setPatterns(array $patterns): self
    {
        $this->patterns = array_merge($this->patterns, $patterns);
        return $this;
    }

    /**
     * Set the default pattern for parameters without specific patterns.
     *
     * This pattern is used when no inline pattern or token pattern is available
     * for a parameter. The pattern should not include delimiters as they are
     * added automatically during compilation.
     *
     * @param string $pattern Regex pattern without delimiters
     * @return self Fluent interface for method chaining
     *
     * @example
     * // Allow any characters including slashes (be careful with this)
     * $compiler->setDefaultPattern('.*');
     *
     * // Only alphanumeric characters
     * $compiler->setDefaultPattern('[a-zA-Z0-9]+');
     */
    public function setDefaultPattern(string $pattern): self
    {
        $this->defaultPattern = $this->escapeSlashes($pattern);
        return $this;
    }

    /**
     * Compile a route pattern into a RouteCompileResult.
     *
     * This is the main compilation method that transforms route patterns with
     * square bracket parameters into regular expressions for URL matching.
     *
     * Process:
     * 1. Tokenize the route pattern into literal and parameter segments
     * 2. Validate parameter names for PHP compatibility
     * 3. Process optional parameters for proper slash handling
     * 4. Build regex components for each token
     * 5. Combine into final regex with named capturing groups
     * 6. Extract parameter names and optional parameter lists
     *
     * @param string $route Route pattern with square bracket parameters
     * @return RouteCompileResult Compiled route with regex and parameter metadata
     *
     * @throws \InvalidArgumentException If parameter names are invalid or brackets are unclosed
     *
     * @example
     * // Simple required parameter
     * $result = $compiler->compile('/users/[id]');
     * // Result: regex = '/^\/users\/(?P<id>\d+)$/', parameters = ['id']
     *
     * // Optional parameter
     * $result = $compiler->compile('/posts/[?category]');
     * // Result: regex with optional group, optionalParameters = ['category']
     *
     * // Mixed parameters with patterns
     * $result = $compiler->compile('/api/[version]/users/[id:\d+]/[?format:json|xml]');
     * // Complex regex with multiple named groups and optional handling
     */
    public function compile(string $route): RouteCompileResult
    {
        $parameters = [];
        $optionalParameters = [];
        $regexParts = [];

        $tokens = $this->tokenize($route);
        $tokens = $this->processOptionalTokens($tokens);

        foreach ($tokens as $token) {
            switch ($token['type']) {
                case 'literal':
                    $regexParts[] = preg_quote($token['value'], '/');
                    break;

                case 'parameter':
                    $this->validateParameterName($token['name']);
                    $pattern = $this->resolvePattern($token['name'], $token['pattern']);
                    $parameters[] = $token['name'];

                    if ($token['optional']) {
                        $optionalParameters[] = $token['name'];
                         // Simple optional without slash handling
                        $regexParts[] = "(?P<{$token['name']}>{$pattern})?";
                    } else {
                        $regexParts[] = "(?P<{$token['name']}>{$pattern})";
                    }
                    break;

                case 'optional_group':
                    // Proper optional group with slash
                    $this->validateParameterName($token['name']);
                    $pattern = $this->resolvePattern($token['name'], $token['pattern']);
                    $parameters[] = $token['name'];
                    $optionalParameters[] = $token['name'];

                    $prefix = preg_quote($token['prefix'], '/');
                    $regexParts[] = "(?:{$prefix}(?P<{$token['name']}>{$pattern}))?";
                    break;
            }
        }

        $regex = '/^' . implode('', $regexParts) . '$/';
        return new RouteCompileResult($regex, $parameters, $optionalParameters);
    }

    /**
     * Check if a route pattern contains parameters.
     *
     * Quick check to determine if a route has dynamic segments that need
     * regex matching, or if it's a static route that can use simple string
     * comparison for better performance.
     *
     * @param string $route Route pattern to check
     * @return bool True if route contains square bracket parameters
     *
     * @example
     * $compiler->isParametrized('/users');           // false - static route
     * $compiler->isParametrized('/users/[id]');      // true - has parameters
     * $compiler->isParametrized('/api/v1/health');   // false - static route
     */
    public function isParametrized(string $route): bool
    {
        return str_contains($route, '[') && str_contains($route, ']');
    }

    /**
     * Compile multiple route patterns at once.
     *
     * Batch compilation method for processing multiple routes efficiently.
     * Useful when loading routes from configuration or setting up route collections.
     *
     * @param array<string, string> $routes Routes to compile (key => pattern)
     * @return array<string, RouteCompileResult> Compiled results with same keys
     *
     * @example
     * $routes = [
     *     'users.index' => '/users',
     *     'users.show' => '/users/[id]',
     *     'posts.category' => '/posts/[?category]/[slug]'
     * ];
     * $results = $compiler->compileMultiple($routes);
     * // Returns: ['users.index' => RouteCompileResult, ...]
     */
    public function compileMultiple(array $routes): array
    {
        $results = [];
        foreach ($routes as $key => $route) {
            $results[$key] = $this->compile($route);
        }
        return $results;
    }

    /**
     * Generate URL from route template and parameters.
     *
     * Reverse compilation that takes a route pattern and parameter values
     * to generate actual URLs. This is used for URL generation in applications.
     *
     * Features:
     * - Required parameters: [name] must be provided or throws exception
     * - Optional parameters: [?name] can be omitted (removes from URL)
     * - Optional with values: [?name] with value adds /value to URL
     * - Pattern validation: Inline patterns [name:pattern] are ignored during generation
     * - Slash normalization: Multiple slashes are collapsed, trailing slashes removed
     *
     * @param string $template Route pattern template with square bracket parameters
     * @param array<string, mixed> $parameters Parameter values for substitution
     * @return string Generated URL with parameters substituted
     *
     * @throws \InvalidArgumentException If required parameters are missing
     *
     * @example
     * // Required parameters
     * $url = $compiler->generate('/users/[id]', ['id' => 123]);
     * // Result: '/users/123'
     *
     * // Optional parameters
     * $url = $compiler->generate('/posts/[?category]/[slug]', ['slug' => 'hello']);
     * // Result: '/posts/hello' (category omitted)
     *
     * $url = $compiler->generate('/posts/[?category]/[slug]', ['category' => 'tech', 'slug' => 'hello']);
     * // Result: '/posts/tech/hello'
     *
     * // With inline patterns (pattern ignored during generation)
     * $url = $compiler->generate('/users/[id:\d+]', ['id' => 123]);
     * // Result: '/users/123'
     */
    public function generate(string $template, array $parameters): string
    {
        // Handle required parameters: [name] or [name:pattern]
        $url = preg_replace_callback(
            '/\[([^?:\]]+)(?::[^]]+)?]/',
            function ($matches) use ($parameters): string {
                $paramName = $matches[1];
                if (isset($parameters[$paramName])) {
                    return (string) $parameters[$paramName];
                }
                throw new \InvalidArgumentException("Required parameter '{$paramName}' not provided");
            },
            $template
        );

        // Handle optional parameters: [?name] or [?name:pattern]
        $url = preg_replace_callback(
            '/\[\?([^:\]]+)(?::[^]]+)?]/',
            function ($matches) use ($parameters): string {
                $paramName = $matches[1];
                if (isset($parameters[$paramName]) && $parameters[$paramName] !== null && $parameters[$paramName] !== '') {
                    return '/' . $parameters[$paramName];
                }
                return '';
            },
            $url
        );

        // Normalize slashes: collapse multiple slashes into single slash
        $url = preg_replace('/\/+/', '/', $url);

        // Remove trailing slash except for root path
        if ($url !== '/' && str_ends_with($url, '/')) {
            $url = rtrim($url, '/');
        }

        return $url;
    }

    /**
     * Validate parameter name according to PHP variable naming conventions.
     *
     * Ensures that parameter names follow valid PHP variable naming rules:
     * - Must start with a letter (a-z, A-Z) or underscore (_)
     * - Can contain letters, numbers (0-9), and underscores
     * - Cannot start with numbers
     * - Cannot contain special characters like hyphens, dots, or spaces
     *
     * @param string $name Parameter name to validate
     * @return void
     * @throws \InvalidArgumentException If parameter name is invalid
     *
     * @example
     * // Valid names
     * $this->validateParameterName('id');        // Valid
     * $this->validateParameterName('user_id');   // Valid
     * $this->validateParameterName('_private');  // Valid
     * $this->validateParameterName('name2');     // Valid
     *
     * // Invalid names
     * $this->validateParameterName('2name');     // Starts with number
     * $this->validateParameterName('user-id');   // Contains hyphen
     * $this->validateParameterName('user.id');   // Contains dot
     * $this->validateParameterName('user id');   // Contains space
     */
    private function validateParameterName(string $name): void
    {
        if (empty($name)) {
            throw new \InvalidArgumentException('Parameter name cannot be empty');
        }

        // Check if parameter name follows PHP variable naming conventions
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid parameter name "%s". Parameter names must start with a letter or underscore, ' .
                    'followed by letters, numbers, or underscores only.',
                    $name
                )
            );
        }
    }

    /**
     * Tokenize route pattern into structured tokens.
     *
     * Parses the route string into an array of tokens representing literal
     * text segments and parameter segments. This is the first step in the
     * compilation process.
     *
     * Token Types:
     * - literal: Static text segments
     * - parameter: Dynamic segments with square bracket syntax
     *
     * @param string $route Route pattern to tokenize
     * @return array<array{type: string, value?: string, name?: string, optional?: bool, pattern?: string|null}>
     *               Array of parsed tokens
     *
     * @throws \InvalidArgumentException If brackets are unclosed
     *
     * @example
     * // Input: '/users/[id]/posts'
     * // Output: [
     * //   ['type' => 'literal', 'value' => '/users/'],
     * //   ['type' => 'parameter', 'name' => 'id', 'optional' => false, 'pattern' => null],
     * //   ['type' => 'literal', 'value' => '/posts']
     * // ]
     */
    private function tokenize(string $route): array
    {
        $tokens = [];
        $length = strlen($route);
        $i = 0;
        $literalStart = 0;

        while ($i < $length) {
            if ($route[$i] === '[') {
                // Save any literal text before this parameter
                if ($i > $literalStart) {
                    $literal = substr($route, $literalStart, $i - $literalStart);
                    if ($literal !== '') {
                        $tokens[] = ['type' => 'literal', 'value' => $literal];
                    }
                }

                // Find matching closing bracket (handle nested brackets)
                $paramStart = $i + 1;
                $bracketCount = 1;
                $i++;

                while ($i < $length && $bracketCount > 0) {
                    if ($route[$i] === '[') {
                        $bracketCount++;
                    } elseif ($route[$i] === ']') {
                        $bracketCount--;
                    }
                    $i++;
                }

                if ($bracketCount > 0) {
                    throw new \InvalidArgumentException("Unclosed parameter bracket in route");
                }

                // Parse parameter content
                $paramContent = substr($route, $paramStart, $i - $paramStart - 1);
                $tokens[] = $this->parseParameter($paramContent);

                $literalStart = $i;
            } else {
                $i++;
            }
        }

        // Add any remaining literal text
        if ($literalStart < $length) {
            $literal = substr($route, $literalStart);
            if ($literal !== '') {
                $tokens[] = ['type' => 'literal', 'value' => $literal];
            }
        }

        return $tokens;
    }

    /**
     * Process optional tokens for proper slash handling.
     *
     * Analyzes token sequences to identify optional parameters that need
     * special slash handling. When an optional parameter follows a literal
     * ending with a slash, it creates an 'optional_with_slash' token that
     * includes the slash in the optional group.
     *
     * This ensures URLs like '/users' and '/users/123' both work correctly
     * when the route pattern is '/users/[?id]'.
     *
     * @param array<array{type: string, value?: string, name?: string, optional?: bool, pattern?: string|null}> $tokens
     *              Input tokens from tokenization
     * @return array<array{type: string, value?: string, name?: string, optional?: bool, pattern?: string|null, slash?: string}>
     *               Processed tokens with slash handling
     *
     * @example
     * // Input tokens: [literal '/users/', parameter '?id']
     * // Output: [literal '/users', optional_with_slash 'id' with slash '/']
     * // This allows both '/users' and '/users/123' to match
     */
    private function processOptionalTokens(array $tokens): array
    {
        $processed = [];
        $i = 0;

        while ($i < count($tokens)) {
            $current = $tokens[$i];
            $next = $tokens[$i + 1] ?? null;

            if ($current['type'] === 'literal' &&
                $next &&
                $next['type'] === 'parameter' &&
                $next['optional']) {

                $literalValue = $current['value'];

                if (str_ends_with($literalValue, '/')) {
                    $pathWithoutSlash = rtrim($literalValue, '/');

                    if ($pathWithoutSlash !== '') {
                        $processed[] = ['type' => 'literal', 'value' => $pathWithoutSlash];
                    }

                    $processed[] = [
                        'type' => 'optional_group',
                        'name' => $next['name'],
                        'pattern' => $next['pattern'],
                        'prefix' => '/', // The slash that should be included in optional group
                    ];

                    $i += 2;
                } else {
                    $processed[] = $current;
                    $i++;
                }
            } else {
                $processed[] = $current;
                $i++;
            }
        }

        return $processed;
    }

    /**
     * Parse parameter content from within square brackets.
     *
     * Analyzes the content inside square brackets to extract parameter
     * name, optional flag, and inline pattern information.
     *
     * Supported formats:
     * - 'name' → required parameter
     * - '?name' → optional parameter
     * - 'name:pattern' → required with inline pattern
     * - '?name:pattern' → optional with inline pattern
     *
     * @param string $content Content inside square brackets (without brackets)
     * @return array{type: string, name: string, optional: bool, pattern: string|null}
     *               Parsed parameter information
     *
     * @example
     * parseParameter('id') → ['type' => 'parameter', 'name' => 'id', 'optional' => false, 'pattern' => null]
     * parseParameter('?category') → ['type' => 'parameter', 'name' => 'category', 'optional' => true, 'pattern' => null]
     * parseParameter('id:\d+') → ['type' => 'parameter', 'name' => 'id', 'optional' => false, 'pattern' => '\d+']
     * parseParameter('?format:json|xml') → ['type' => 'parameter', 'name' => 'format', 'optional' => true, 'pattern' => 'json|xml']
     */
    private function parseParameter(string $content): array
    {
        $isOptional = false;
        $paramName = '';
        $pattern = null;

        // Check for optional marker
        if (str_starts_with($content, '?')) {
            $isOptional = true;
            $content = substr($content, 1);
        }

        // Split name and pattern on first colon
        $colonPos = strpos($content, ':');
        if ($colonPos !== false) {
            $paramName = substr($content, 0, $colonPos);
            $pattern = substr($content, $colonPos + 1);
        } else {
            $paramName = $content;
        }

        return [
            'type' => 'parameter',
            'name' => $paramName,
            'optional' => $isOptional,
            'pattern' => $pattern
        ];
    }

    /**
     * Escape forward slashes in regex patterns.
     *
     * Prepares regex patterns for use in delimited regular expressions
     * by escaping forward slashes that would conflict with the delimiter.
     *
     * @param string $pattern Input regex pattern
     * @return string Pattern with escaped forward slashes
     *
     * @example
     * escapeSlashes('a/b/c') → 'a\/b\/c'
     * escapeSlashes('\d+') → '\d+' (unchanged)
     */
    private function escapeSlashes(string $pattern): string
    {
        return str_replace('/', '\/', $pattern);
    }

    private function resolvePattern(string $paramName, ?string $customPattern): string
    {
        if ($customPattern !== null) {
            return $this->escapeSlashes($customPattern);
        }

        if (isset($this->patterns[$paramName])) {
            return $this->patterns[$paramName];
        }

        return static::DEFAULT_PATTERNS[$paramName] ?? $this->defaultPattern;
    }
}