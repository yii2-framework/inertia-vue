<?php

declare(strict_types=1);

namespace yii\inertia\vue\tests\support\stub;

/**
 * Centralized stub for internal PHP function mocks.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 0.1
 */
final class MockerFunctions
{
    private static bool $fileGetContentsShouldFail = false;

    public static function file_get_contents(string $filename, mixed ...$args): string|false
    {
        if (self::$fileGetContentsShouldFail) {
            return false;
        }

        return \file_get_contents($filename, ...$args); // @phpstan-ignore argument.type
    }

    public static function reset(): void
    {
        self::$fileGetContentsShouldFail = false;
    }

    public static function setFileGetContentsShouldFail(bool $shouldFail = true): void
    {
        self::$fileGetContentsShouldFail = $shouldFail;
    }
}
