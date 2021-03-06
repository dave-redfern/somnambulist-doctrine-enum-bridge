<?php

namespace Somnambulist\Tests\DoctrineEnumBridge\Helpers;

use Somnambulist\Tests\DoctrineEnumBridge\Enum\Gender;

/**
 * Class Serializer
 *
 * @package    Somnambulist\Tests\DoctrineEnumBridge\Helpers
 * @subpackage Somnambulist\Tests\DoctrineEnumBridge\Helpers\Serializer
 */
class Serializer
{

    /**
     * @param Gender $value
     *
     * @return string
     */
    public function __invoke($value) {
        return $value->value();
    }
}
