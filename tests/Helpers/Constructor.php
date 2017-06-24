<?php

namespace Somnambulist\Tests\DoctrineEnumBridge\Helpers;

use Somnambulist\Tests\DoctrineEnumBridge\Enum\Gender;

/**
 * Class Constructor
 *
 * @package    Somnambulist\Tests\DoctrineEnumBridge\Helpers
 * @subpackage Somnambulist\Tests\DoctrineEnumBridge\Helpers\Constructor
 */
class Constructor
{
    public function __invoke($value)
    {
        if (null !== $gender = Gender::memberOrNullByValue($value)) {
            return $gender;
        }

        throw new \InvalidArgumentException(sprintf('"%s" not valid for "%s"', $value, Gender::class));
    }
}
