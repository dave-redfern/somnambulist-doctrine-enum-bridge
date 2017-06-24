<?php

namespace Somnambulist\Tests\DoctrineEnumBridge\Enum;

use Eloquent\Enumeration\AbstractEnumeration;

/**
 * Class Gender
 *
 * @method static Gender MALE()
 * @method static Gender FEMALE()
 */
class Gender extends AbstractEnumeration
{

    const MALE = 'male';
    const FEMALE = 'female';

}
