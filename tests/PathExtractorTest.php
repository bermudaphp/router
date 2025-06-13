<?php

declare(strict_types=1);

namespace Bermuda\Router\Tests;

use Bermuda\Router\PathExtractor;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\Group;

#[Group('path-extractor')]
#[TestDox('PathExtractor tests')]
final class PathExtractorTest extends TestCase
{
    use PathExtractor;

    #[Test]
    #[DataProvider('pathExtractionProvider')]
    #[TestDox('Can extract and normalize paths correctly')]
    public function can_extract_and_normalize_paths_correctly(string $input, string $expected, string $description): void
    {
        $result = $this->extractPath($input);

        $this->assertEquals($expected, $result, "Failed for case: $description (input: '$input')");
    }

    public static function pathExtractionProvider(): array
    {
        return [
            // Basic paths
            ['/', '/', 'root path'],
            ['/users', '/users', 'simple path'],
            ['/users/123', '/users/123', 'path with segments'],

            // Paths with query strings
            ['/users?page=1', '/users', 'path with query string'],
            ['/users/123?format=json', '/users/123', 'path with query parameters'],
            ['/search?q=test&limit=10', '/search', 'path with multiple query params'],
            ['/api/v1/posts?category=tech&sort=date', '/api/v1/posts', 'complex query string'],

            // Paths with fragments
            ['/docs#introduction', '/docs', 'path with fragment'],
            ['/users/123#profile', '/users/123', 'path with fragment and segments'],
            ['/page?tab=1#section', '/page', 'path with query and fragment'],

            // Multiple slashes normalization
            ['/users//123', '/users/123', 'double slash normalization'],
            ['//users/123', '/users/123', 'leading double slash'],
            ['///users///123///', '/users/123', 'multiple slashes everywhere'],
            ['/users///', '/users', 'trailing multiple slashes'],

            // Trailing slash handling
            ['/users/', '/users', 'trailing slash removal'],
            ['/users/123/', '/users/123', 'trailing slash on segments'],

            // Backslash handling
            ['/users\\123', '/users/123', 'backslash to forward slash'],
            ['\\users\\123\\', '/users/123', 'multiple backslashes'],
            ['/users\\\\123', '/users/123', 'double backslashes'],

            // URL encoded paths
            ['/users%20list', '/users list', 'URL encoded space'],
            ['/caf%C3%A9', '/café', 'URL encoded UTF-8'],
            ['/path%2Fwith%2Fencoded%2Fslashes', '/path/with/encoded/slashes', 'encoded slashes'],

            // Edge cases
            ['', '/', 'empty string'],
            ['users', '/users', 'path without leading slash'],
            ['users/123', '/users/123', 'relative path normalization'],

            // Complex combinations
            ['//users//123/?page=1#top', '/users/123', 'complex normalization with query and fragment'],
            ['/api\\v1//users\\\\123/', '/api/v1/users/123', 'mixed slashes with normalization'],
            ['%2F%2Fusers%2F%2F123%2F', '/users/123', 'encoded slashes normalized after decode'],

            // Realistic API paths
            ['/api/v1/users/456/posts/789?include=comments', '/api/v1/users/456/posts/789', 'RESTful API path'],
            ['/admin/dashboard/statistics?range=month&format=json', '/admin/dashboard/statistics', 'admin panel path'],
            ['/blog/2024/12/hello-world-post?utm_source=google', '/blog/2024/12/hello-world-post', 'blog post path'],
        ];
    }

    #[Test]
    #[TestDox('Handles various URI schemes correctly')]
    public function handles_various_uri_schemes_correctly(): void
    {
        $testCases = [
            'http://example.com/users' => '/users',
            'https://example.com/users/123' => '/users/123',
            'ftp://files.example.com/path/to/file' => '/path/to/file',
        ];

        foreach ($testCases as $input => $expected) {
            $result = $this->extractPath($input);
            $this->assertEquals($expected, $result, "Failed for URI: $input");
        }
    }

    #[Test]
    #[TestDox('Handles complex real-world URIs')]
    public function handles_complex_real_world_uris(): void
    {
        $realWorldCases = [
            // E-commerce
            '/products/electronics/smartphones/iphone-15?color=blue&storage=256gb&ref=homepage' => '/products/electronics/smartphones/iphone-15',

            // Social media
            '/users/john.doe/posts/2024-holiday-photos?view=gallery&page=2' => '/users/john.doe/posts/2024-holiday-photos',

            // File downloads
            '/downloads/software/my-app-v1.2.3.zip?platform=windows&arch=x64' => '/downloads/software/my-app-v1.2.3.zip',

            // Search results
            '/search/results?q=php%20router&category=tutorials&sort=date&order=desc' => '/search/results',

            // Admin panels
            '/admin/users/permissions/edit?user_id=456&role=moderator&return_url=%2Fadmin%2Fusers' => '/admin/users/permissions/edit',
        ];

        foreach ($realWorldCases as $input => $expected) {
            $result = $this->extractPath($input);
            $this->assertEquals($expected, $result, "Real-world URI failed: $input");
        }
    }

    #[Test]
    #[TestDox('Handles encoded query parameters correctly')]
    public function handles_encoded_query_parameters_correctly(): void
    {
        $testCases = [
            '/search?q=hello%20world' => '/search',
            '/redirect?url=https%3A%2F%2Fexample.com%2Fpath' => '/redirect',
            '/api/posts?filter=%7B%22status%22%3A%22published%22%7D' => '/api/posts', // encoded JSON
            '/callback?token=abc%2B123%2Fdef%3D%3D' => '/callback', // encoded base64-like
        ];

        foreach ($testCases as $input => $expected) {
            $result = $this->extractPath($input);
            $this->assertEquals($expected, $result, "Encoded query handling failed for: $input");
        }
    }

    #[Test]
    #[TestDox('Handles international characters correctly')]
    public function handles_international_characters_correctly(): void
    {
        $internationalPaths = [
            '/café/menü' => '/café/menü',
            '/пользователи/123' => '/пользователи/123',
            '/用户/资料' => '/用户/资料',
            '/árbol/niño' => '/árbol/niño',
        ];

        foreach ($internationalPaths as $input => $expected) {
            $result = $this->extractPath($input);
            $this->assertEquals($expected, $result, "International characters not handled for: $input");
        }
    }

    #[Test]
    #[TestDox('Normalizes paths consistently')]
    public function normalizes_paths_consistently(): void
    {
        // All these should normalize to the same path
        $variants = [
            '/users/123',
            '//users//123',
            '/users///123/',
            '\\users\\123\\',
            '/users/123?ignored=param',
            '/users/123#ignored-fragment',
            'users/123', // without leading slash
            '/users/123?page=1&sort=name#top',
        ];

        $expected = '/users/123';

        foreach ($variants as $variant) {
            $result = $this->extractPath($variant);
            $this->assertEquals($expected, $result, "Inconsistent normalization for: $variant");
        }
    }

    #[Test]
    #[TestDox('Handles root path variations')]
    public function handles_root_path_variations(): void
    {
        $rootVariations = [
            '/' => '/',
            '//' => '/',
            '///' => '/',
            '/?query=param' => '/',
            '/#fragment' => '/',
            '' => '/',
        ];

        foreach ($rootVariations as $input => $expected) {
            $result = $this->extractPath($input);
            $this->assertEquals($expected, $result, "Root path not handled correctly for: '$input'");
        }
    }
}