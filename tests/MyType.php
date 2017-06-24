<?php

namespace Somnambulist\Tests\DoctrineEnumBridge;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Somnambulist\DoctrineEnumBridge\EnumerationBridge;

/**
 * Class MyType
 *
 * @package    Somnambulist\Tests\DoctrineEnumBridge
 * @subpackage Somnambulist\Tests\DoctrineEnumBridge\MyType
 */
class MyType extends EnumerationBridge
{
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return 'FOO BAR';
    }
}
