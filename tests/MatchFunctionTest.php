<?php

declare(strict_types=1);

namespace Bermuda\Router\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\Group;

use function Bermuda\Router\_match;

#[Group('match-function')]
#[TestDox('_match function tests')]
final class MatchFunctionTest extends TestCase
{
    #[Test]
    #[TestDox('_match returns null for non-matching patterns')]
    public function match_returns_null_for_non_matching_patterns(): void
    {
        $pattern = '/^\/users\/(?P<id>[^\/]+)$/';
        $path = '/posts/123';
        $parameters = ['id'];

        $result = _match($pattern, $path, $parameters);

        $this->assertNull($result);
    }

    #[Test]
    #[TestDox('_match extracts single parameter with type conversion')]
    public function match_extracts_single_parameter_with_type_conversion(): void
    {
        $pattern = '/^\/users\/(?P<id>[^\/]+)$/';
        $path = '/users/123';
        $parameters = ['id'];

        $result = _match($pattern, $path, $parameters);

        $this->assertNotNull($result);
        $this->assertEquals(['id' => 123], $result); // Converted to integer
        $this->assertIsInt($result['id']);
    }

    #[Test]
    #[TestDox('_match extracts multiple parameters with correct types')]
    public function match_extracts_multiple_parameters_with_correct_types(): void
    {
        $pattern = '/^\/products\/(?P<id>[^\/]+)\/(?P<price>[^\/]+)\/(?P<name>[^\/]+)$/';
        $path = '/products/123/99.99/laptop';
        $parameters = ['id', 'price', 'name'];

        $result = _match($pattern, $path, $parameters);

        $this->assertNotNull($result);
        $this->assertEquals([
            'id' => 123,         // Integer
            'price' => 99.99,    // Float
            'name' => 'laptop'   // String
        ], $result);

        $this->assertIsInt($result['id']);
        $this->assertIsFloat($result['price']);
        $this->assertIsString($result['name']);
    }

    #[Test]
    #[TestDox('_match applies default values for missing parameters')]
    public function match_applies_default_values_for_missing_parameters(): void
    {
        $pattern = '/^\/posts\/(?P<category>[^\/]+)(?:\/(?P<slug>[^\/]+))?$/';
        $path = '/posts/tech';
        $parameters = ['category', 'slug'];
        $defaults = ['slug' => 'default-slug', 'category' => 'fallback'];

        $result = _match($pattern, $path, $parameters, $defaults);

        $this->assertNotNull($result);
        $this->assertEquals([
            'category' => 'tech',        // Extracted from URL
            'slug' => 'default-slug'     // From defaults
        ], $result);
    }

    #[Test]
    #[TestDox('_match handles empty parameter values')]
    public function match_handles_empty_parameter_values(): void
    {
        $pattern = '/^\/test\/(?P<empty>[^\/]*)\/(?P<id>[^\/]+)$/';
        $path = '/test//123';
        $parameters = ['empty', 'id'];
        $defaults = ['empty' => 'default-empty']; // Добавляем default для пустого параметра

        $result = _match($pattern, $path, $parameters, $defaults);

        $this->assertNotNull($result);
        $this->assertEquals([
            'empty' => 'default-empty',  // ИСПРАВЛЕНО: пустая строка заменяется на default
            'id' => 123                  // Converted to integer
        ], $result);

        $this->assertSame('default-empty', $result['empty']);
        $this->assertIsInt($result['id']);
    }

    #[Test]
    #[TestDox('_match uses defaults when parameter is empty string')]
    public function match_uses_defaults_when_parameter_is_empty_string(): void
    {
        $pattern = '/^\/test\/(?P<param>[^\/]*)$/';
        $path = '/test/';
        $parameters = ['param'];
        $defaults = ['param' => 'default-value'];

        $result = _match($pattern, $path, $parameters, $defaults);

        $this->assertNotNull($result);
        // ИСПРАВЛЕНО: пустая строка из URL заменяется на default
        $this->assertEquals(['param' => 'default-value'], $result);
    }

    #[Test]
    #[TestDox('_match preserves non-empty values over defaults')]
    public function match_preserves_non_empty_values_over_defaults(): void
    {
        $pattern = '/^\/test\/(?P<param>[^\/]*)$/';
        $path = '/test/actual-value';
        $parameters = ['param'];
        $defaults = ['param' => 'default-value'];

        $result = _match($pattern, $path, $parameters, $defaults);

        $this->assertNotNull($result);
        // Непустое значение из URL имеет приоритет над default
        $this->assertEquals(['param' => 'actual-value'], $result);
    }

    #[Test]
    #[TestDox('_match is consistent with number converter behavior')]
    public function match_is_consistent_with_number_converter_behavior(): void
    {
        $pattern = '/^\/test\/(?P<value>.+)$/';
        $parameters = ['value'];

        // Test cases with already decoded values (since $path is already decoded)
        $testCases = [
            [' 123 ', ' 123 '],      // Spaces, should remain string (whitespace preserved)
            ['   ', '   '],          // Only spaces, should remain string (whitespace only)
            ['123abc', '123abc'],    // Should remain string (mixed content)
            ['123', 123],            // Should convert to integer
            ['45.67', 45.67],        // Should convert to float
            ['1e5', 100000.0],       // Should convert to float (scientific)
        ];

        foreach ($testCases as [$inputValue, $expectedValue]) {
            $path = "/test/$inputValue";  // $inputValue уже декодирован
            $result = _match($pattern, $path, $parameters);

            $this->assertNotNull($result, "Failed to match: $path");

            // Сравниваем с ожидаемым значением после конвертации NumberConverter
            $convertedValue = \Bermuda\Stdlib\NumberConverter::convertValue($inputValue);
            $this->assertSame($convertedValue, $result['value'],
                "Inconsistent behavior for input: '$inputValue'");
        }
    }

    #[Test]
    #[TestDox('_match uses defaults when parameter is not captured')]
    public function match_uses_defaults_when_parameter_is_not_captured(): void
    {
        $pattern = '/^\/posts\/(?P<category>[^\/]+)(?:\/(?P<slug>[^\/]+))?$/';
        $path = '/posts/tech';
        $parameters = ['category', 'slug'];
        $defaults = ['slug' => 'default-slug'];

        $result = _match($pattern, $path, $parameters, $defaults);

        $this->assertNotNull($result);
        $this->assertEquals([
            'category' => 'tech',
            'slug' => 'default-slug'  // From defaults
        ], $result);
    }

    #[Test]
    #[DataProvider('numericConversionProvider')]
    #[TestDox('_match performs correct numeric conversions')]
    public function match_performs_correct_numeric_conversions(
        string $input,
        mixed $expectedValue,
        string $expectedType
    ): void {
        $pattern = '/^\/test\/(?P<value>.+)$/';
        $path = "/test/$input";
        $parameters = ['value'];

        $result = _match($pattern, $path, $parameters);

        $this->assertNotNull($result);
        $this->assertSame($expectedValue, $result['value']);
        $this->assertEquals($expectedType, gettype($result['value']));
    }

    public static function numericConversionProvider(): array
    {
        return [
            // [input, expected_value, expected_type]
            ['123', 123, 'integer'],
            ['-456', -456, 'integer'],
            ['0', 0, 'integer'],
            ['78.90', 78.90, 'double'],
            ['-12.34', -12.34, 'double'],
            ['0.0', 0.0, 'double'],
            ['1e5', 100000.0, 'double'],
            ['2.5e-3', 0.0025, 'double'],
            ['-1E3', -1000.0, 'double'],
            ['hello', 'hello', 'string'],
            ['123abc', '123abc', 'string'],
            ['abc123', 'abc123', 'string'],
            ['+789', 789, 'integer'],
            ['+45.67', 45.67, 'double'],
        ];
    }

    #[Test]
    #[TestDox('_match handles scientific notation correctly')]
    public function match_handles_scientific_notation_correctly(): void
    {
        $pattern = '/^\/science\/(?P<small>[^\/]+)\/(?P<large>[^\/]+)\/(?P<negative>[^\/]+)$/';
        $path = '/science/1.23e-4/2e5/-3.14e2';
        $parameters = ['small', 'large', 'negative'];

        $result = _match($pattern, $path, $parameters);

        $this->assertNotNull($result);
        $this->assertEqualsWithDelta(0.000123, $result['small'], 0.0000001);
        $this->assertEquals(200000.0, $result['large']);
        $this->assertEquals(-314.0, $result['negative']);

        // All should be floats
        $this->assertIsFloat($result['small']);
        $this->assertIsFloat($result['large']);
        $this->assertIsFloat($result['negative']);
    }

    #[Test]
    #[TestDox('_match handles mixed parameter types')]
    public function match_handles_mixed_parameter_types(): void
    {
        $pattern = '/^\/mixed\/(?P<int>[^\/]+)\/(?P<float>[^\/]+)\/(?P<string>[^\/]+)\/(?P<scientific>[^\/]+)\/(?P<mixed>[^\/]+)$/';
        $path = '/mixed/42/3.14/hello/1e3/123abc';
        $parameters = ['int', 'float', 'string', 'scientific', 'mixed'];

        $result = _match($pattern, $path, $parameters);

        $this->assertNotNull($result);
        $this->assertEquals([
            'int' => 42,
            'float' => 3.14,
            'string' => 'hello',
            'scientific' => 1000.0,
            'mixed' => '123abc'
        ], $result);

        // Check types
        $this->assertIsInt($result['int']);
        $this->assertIsFloat($result['float']);
        $this->assertIsString($result['string']);
        $this->assertIsFloat($result['scientific']);
        $this->assertIsString($result['mixed']);
    }

    #[Test]
    #[TestDox('_match performance is acceptable for repeated calls')]
    public function match_performance_is_acceptable_for_repeated_calls(): void
    {
        $pattern = '/^\/api\/(?P<version>[^\/]+)\/users\/(?P<id>[^\/]+)\/posts\/(?P<slug>[^\/]+)$/';
        $path = '/api/v1/users/123/posts/hello-world';
        $parameters = ['version', 'id', 'slug'];

        $startTime = microtime(true);

        for ($i = 0; $i < 50000; $i++) {
            $result = _match($pattern, $path, $parameters);
            $this->assertNotNull($result);
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Should complete 50,000 matches in reasonable time (< 1 second)
        $this->assertLessThan(1.0, $duration,
            '_match function should handle 50,000 operations quickly');

        echo "\nPerformance: 50,000 _match calls in " .
            number_format($duration * 1000, 2) . "ms\n";
    }

    #[Test]
    #[TestDox('_match handles complex optional parameter patterns')]
    public function match_handles_complex_optional_parameter_patterns(): void
    {
        $pattern = '/^\/blog(?:\/(?P<year>[^\/]+))?(?:\/(?P<month>[^\/]+))?(?:\/(?P<slug>[^\/]+))?$/';
        $parameters = ['year', 'month', 'slug'];
        $defaults = ['year' => 2024, 'month' => 1, 'slug' => 'default'];

        // Test all combinations
        $testCases = [
            ['/blog', ['year' => 2024, 'month' => 1, 'slug' => 'default']],
            ['/blog/2023', ['year' => 2023, 'month' => 1, 'slug' => 'default']],
            ['/blog/2023/12', ['year' => 2023, 'month' => 12, 'slug' => 'default']],
            ['/blog/2023/12/hello', ['year' => 2023, 'month' => 12, 'slug' => 'hello']],
        ];

        foreach ($testCases as [$path, $expected]) {
            $result = _match($pattern, $path, $parameters, $defaults);
            $this->assertNotNull($result, "Failed to match: $path");
            $this->assertEquals($expected, $result, "Incorrect result for: $path");
        }
    }

    #[Test]
    #[TestDox('_match preserves parameter order')]
    public function match_preserves_parameter_order(): void
    {
        $pattern = '/^\/ordered\/(?P<third>[^\/]+)\/(?P<first>[^\/]+)\/(?P<second>[^\/]+)$/';
        $path = '/ordered/c/a/b';
        $parameters = ['first', 'second', 'third']; // Different order than in regex

        $result = _match($pattern, $path, $parameters);

        $this->assertNotNull($result);

        // Should extract in the order specified by $parameters array
        $keys = array_keys($result);
        $this->assertEquals(['first', 'second', 'third'], $keys);
        $this->assertEquals(['first' => 'a', 'second' => 'b', 'third' => 'c'], $result);
    }

    #[Test]
    #[TestDox('_match handles edge cases with special characters')]
    public function match_handles_edge_cases_with_special_characters(): void
    {
        $pattern = '/^\/special\/(?P<value>.+)$/';
        $parameters = ['value'];

        $testCases = [
            ['hello-world', 'hello-world'],
            ['hello_world', 'hello_world'],
            ['hello.world', 'hello.world'],
            ['hello+world', 'hello+world'],
            ['hello@world', 'hello@world'],
            ['123-abc-456', '123-abc-456'],
        ];

        foreach ($testCases as [$input, $expected]) {
            $path = "/special/$input";
            $result = _match($pattern, $path, $parameters);

            $this->assertNotNull($result, "Failed to match: $path");
            $this->assertEquals(['value' => $expected], $result);
        }
    }
}