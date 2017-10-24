<?php

namespace Somnambulist\Tests\DoctrineEnumBridge\Helpers;

use Somnambulist\Tests\DoctrineEnumBridge\Enum\NullableType;

/**
 * Class NullableConstructor
 *
 * @package    Somnambulist\Tests\DoctrineEnumBridge\Helpers
 * @subpackage Somnambulist\Tests\DoctrineEnumBridge\Helpers\NullableConstructor
 */
class NullableConstructor
{
    public function __invoke($value)
    {
        if (null !== $enum = NullableType::memberOrNullByValue($value)) {
            return $enum;
        }

        throw new \InvalidArgumentException(sprintf('"%s" not valid for "%s"', $value, NullableType::class));
    }
}
