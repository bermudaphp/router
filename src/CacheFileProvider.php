<?php

declare(strict_types=1);

namespace Bermuda\Router;

use Bermuda\VarExport\VarExporter;

/**
 * Cache file provider for saving cached route data to PHP files
 *
 * This class provides functionality to write array data as executable PHP files
 * with optional docblock generation based on context variables.
 */
final class CacheFileProvider
{
    private(set) string $cacheDirectory;

    public function __construct(
        ?string $cacheDirectory = null,
        private readonly int $permissions = 0755
    ) {
        if (!$cacheDirectory) $cacheDirectory = getcwd() . DIRECTORY_SEPARATOR
            . 'config' . DIRECTORY_SEPARATOR . 'cache';
        $this->cacheDirectory = $cacheDirectory;

        $this->ensureCacheDirectoryExists();
    }

    /**
     * Write array data to file
     *
     * @param string $filename File name (without extension)
     * @param array $data Array data to write
     * @param array $context Context variables for docblock generation
     * @return bool Success status
     * @throws \RuntimeException On write error
     */
    public function writeFile(string $filename, array $data, array $context = []): bool
    {
        $this->validateFilename($filename);

        $filePath = $this->buildFilePath($filename);

        try {
            $docblock = $this->generateDocblock($context);
            $exportedData = VarExporter::exportPretty($data);

            $phpCode = "<?php\n\n" . $docblock . "return " . $exportedData . ";\n";

            $bytesWritten = file_put_contents(
                filename: $filePath,
                data: $phpCode,
                flags: LOCK_EX
            );

            if ($bytesWritten === false) {
                throw new \RuntimeException("Failed to write data to file: $filePath");
            }

            return true;

        } catch (\Throwable $e) {
            throw new \RuntimeException(
                "Unexpected error while writing file $filePath: " . $e->getMessage(),
                previous: $e
            );
        }
    }

    /**
     * Check if cache file exists
     */
    public function fileExists(string $filename): bool
    {
        return file_exists($this->buildFilePath($filename));
    }

    /**
     * Delete cache file
     */
    public function deleteFile(string $filename): bool
    {
        $filePath = $this->buildFilePath($filename);

        if (!file_exists($filePath)) {
            return true;
        }

        return unlink($filePath);
    }

    /**
     * Read data from cache file
     */
    public function readFile(string $filename): array
    {
        $filePath = $this->buildFilePath($filename);

        if (!file_exists($filePath)) {
            throw new \RuntimeException("Cache file not found: $filePath");
        }

        try {
            $data = include $filePath;

            if (!is_array($data)) {
                throw new \RuntimeException("Cache file contains incorrect data: $filePath");
            }

            return $data;

        } catch (\Throwable $e) {
            throw new \RuntimeException(
                "Error reading cache file $filePath: " . $e->getMessage(),
                previous: $e
            );
        }
    }

    /**
     * Ensure cache directory exists
     */
    private function ensureCacheDirectoryExists(): void
    {
        if (!is_dir($this->cacheDirectory)) {
            if (!mkdir($this->cacheDirectory, $this->permissions, true)) {
                throw new \RuntimeException(
                    "Failed to create cache directory: $this->cacheDirectory"
                );
            }
        }
    }

    /**
     * Build full file path
     */
    private function buildFilePath(string $filename): string
    {
        return $this->cacheDirectory . '/' . $filename . '.php';
    }

    /**
     * Generate docblock based on context variables
     *
     * @param array $context Context variables
     * @return string Generated docblock
     */
    private function generateDocblock(array $context): string
    {
        if (empty($context)) {
            return '';
        }

        $docLines = ['/**'];

        foreach ($context as $varName => $value) {
            $type = $this->getPhpType($value);
            $docLines[] = " * @var $type $$varName";
        }

        $docLines[] = ' */';

        return implode("\n", $docLines) . "\n\n";
    }

    /**
     * Determine PHP type of variable for docblock
     *
     * @param mixed $value Variable value
     * @return string PHP type
     */
    private function getPhpType(mixed $value): string
    {
        return match (true) {
            is_object($value) => $this->getObjectType($value),
            is_array($value) => $this->getArrayType($value),
            is_string($value) => 'string',
            is_int($value) => 'int',
            is_float($value) => 'float',
            is_bool($value) => 'bool',
            is_null($value) => 'mixed',
            is_resource($value) => 'resource',
            is_callable($value) => 'callable',
            default => 'mixed'
        };
    }

    /**
     * Determine object type
     *
     * @param object $object Object
     * @return string Object type
     */
    private function getObjectType(object $object): string
    {
        $className = get_class($object);

        // Simplify full class names for built-in types
        return match ($className) {
            'stdClass' => 'object',
            'DateTime' => 'DateTime',
            'DateTimeImmutable' => 'DateTimeImmutable',
            default => '\\' . ltrim($className, '\\')
        };
    }

    /**
     * Determine array type
     *
     * @param array $array Array
     * @return string Array type
     */
    private function getArrayType(array $array): string
    {
        if (empty($array)) {
            return 'array';
        }

        // Check if array is a list (indexed)
        if (array_is_list($array)) {
            $firstElement = reset($array);
            $elementType = $this->getPhpType($firstElement);
            return "array<$elementType>";
        }

        // Associative array
        $firstKey = array_key_first($array);
        $firstValue = $array[$firstKey];

        $keyType = is_string($firstKey) ? 'string' : 'int';
        $valueType = $this->getPhpType($firstValue);

        return "array<$keyType, $valueType>";
    }

    /**
     * Validate filename
     */
    private function validateFilename(string $filename): void
    {
        if (empty($filename)) {
            throw new \InvalidArgumentException('Filename cannot be empty');
        }

        if (preg_match('/[\/\\\\:*?"<>|]/', $filename)) {
            throw new \InvalidArgumentException(
                'Filename contains invalid characters: ' . $filename
            );
        }

        if (strlen($filename) > 255) {
            throw new \InvalidArgumentException('Filename is too long');
        }
    }
}