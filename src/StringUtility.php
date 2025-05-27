<?php

namespace Dktaylor\BundleGeneratorBundle;

class StringUtility
{
    public const int STRING_PASCAL_TO_KEBAB = 0;
    public const int STRING_PASCAL_TO_SNAKE = 1;

    private const array CONVERSION_TYPES = [
        self::STRING_PASCAL_TO_KEBAB,
        self::STRING_PASCAL_TO_SNAKE,
    ];

    private const array REPLACEMENT_MAP = [
        self::STRING_PASCAL_TO_KEBAB => '-$0',
        self::STRING_PASCAL_TO_SNAKE => '_$0',
    ];

    private const array CONVERSION_REQUIRES_LOWERCASE = [
        self::STRING_PASCAL_TO_KEBAB,
        self::STRING_PASCAL_TO_SNAKE,
    ];

    public static function convertString(string $string, int $conversionType): string
    {
        if (!in_array($conversionType, self::CONVERSION_TYPES)) {
            throw new \RuntimeException('Invalid string conversion type: '. filter_var($conversionType, FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        }

        $converted = preg_replace('~(?<=\\w|\w|/\w)([A-Z])~', self::REPLACEMENT_MAP[$conversionType], $string);

        if (in_array($conversionType, self::CONVERSION_REQUIRES_LOWERCASE)) {
            $converted = strtolower($converted);
        }

        return $converted;
    }
}