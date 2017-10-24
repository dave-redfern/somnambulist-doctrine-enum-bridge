<?php

namespace Somnambulist\Tests\DoctrineEnumBridge;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Somnambulist\DoctrineEnumBridge\EnumerationBridge;
use Somnambulist\Tests\DoctrineEnumBridge\Enum\Action;
use Somnambulist\Tests\DoctrineEnumBridge\Enum\Gender;
use PHPUnit\Framework\TestCase;
use Somnambulist\Tests\DoctrineEnumBridge\Enum\NullableType;
use Somnambulist\Tests\DoctrineEnumBridge\Helpers\Constructor;
use Somnambulist\Tests\DoctrineEnumBridge\Helpers\NullableConstructor;
use Somnambulist\Tests\DoctrineEnumBridge\Helpers\Serializer;

/**
 * Class EnumerationBridgeTest
 *
 * Tests adapted from:
 * https://github.com/acelaya/doctrine-enum-type/blob/master/tests/Type/PhpEnumTypeTest.php
 *
 * @package    Somnambulist\Tests\DoctrineEnumBridge
 * @subpackage Somnambulist\Tests\DoctrineEnumBridge\EnumerationBridgeTest
 */
class EnumerationBridgeTest extends TestCase
{
    /**
     * @var ObjectProphecy
     */
    protected $platform;

    public function setUp()
    {
        $this->platform = $this->prophesize(AbstractPlatform::class);

        // Before every test, clean registered types
        $refProp = new \ReflectionProperty(Type::class, '_typeObjects');
        $refProp->setAccessible(true);
        $refProp->setValue(null, []);

        $refProp = new \ReflectionProperty(Type::class, '_typesMap');
        $refProp->setAccessible(true);
        $refProp->setValue(null, []);
    }

    /**
     * @test
     */
    public function enumTypesAreProperlyRegistered()
    {
        $this->assertFalse(Type::hasType(Action::class));
        $this->assertFalse(Type::hasType('gender'));

        EnumerationBridge::registerEnumType(Action::class, function ($value) {
            if (Action::isValid($value)) {
                return new Action($value);
            }

            throw new \InvalidArgumentException(sprintf('"%s" not valid for "%s"', $value, Action::class));
        });
        EnumerationBridge::registerEnumTypes([
            'gender' => function ($value) {
                if (null !== $gender = Gender::memberOrNullByValue($value)) {
                    return $gender;
                }

                throw new \InvalidArgumentException(sprintf('"%s" not valid for "%s"', $value, Gender::class));
            },
        ]);

        $this->assertTrue(Type::hasType(Action::class));
        $this->assertTrue(Type::hasType('gender'));
    }

    /**
     * @test
     */
    public function enumTypesAreProperlyCustomizedWhenRegistered()
    {
        $this->assertFalse(Type::hasType(Action::class));
        $this->assertFalse(Type::hasType(Gender::class));

        EnumerationBridge::registerEnumTypes([
            Action::class => function ($value) {
                if (Action::isValid($value)) {
                    return new Action($value);
                }

                throw new \InvalidArgumentException(sprintf('"%s" not valid for "%s"', $value, Action::class));
            },
            'gender' => function ($value) {
                if (null !== $gender = Gender::memberOrNullByValue($value)) {
                    return $gender;
                }

                throw new \InvalidArgumentException(sprintf('"%s" not valid for "%s"', $value, Gender::class));
            },
        ]);

        /** @var Type $actionType */
        $actionType = Type::getType(Action::class);
        $this->assertInstanceOf(EnumerationBridge::class, $actionType);
        $this->assertEquals(Action::class, $actionType->getName());

        /** @var Type $actionType */
        $genderType = Type::getType('gender');
        $this->assertInstanceOf(EnumerationBridge::class, $genderType);
        $this->assertEquals('gender', $genderType->getName());
    }

    /**
     * @test
     */
    public function canAssignInvokableObjectInstances()
    {
        $this->assertFalse(Type::hasType(Action::class));
        $this->assertFalse(Type::hasType(Gender::class));

        EnumerationBridge::registerEnumTypes([
            Action::class => function ($value) {
                if (Action::isValid($value)) {
                    return new Action($value);
                }

                throw new \InvalidArgumentException(sprintf('"%s" not valid for "%s"', $value, Action::class));
            },
            'gender' => [new Constructor(), new Serializer()],
            'gender2' => new Constructor(),
        ]);

        /** @var Type $actionType */
        $actionType = Type::getType(Action::class);
        $this->assertInstanceOf(EnumerationBridge::class, $actionType);
        $this->assertEquals(Action::class, $actionType->getName());

        /** @var Type $actionType */
        $genderType = Type::getType('gender');
        $this->assertInstanceOf(EnumerationBridge::class, $genderType);
        $this->assertEquals('gender', $genderType->getName());
    }

    /**
     * @test
     */
    public function getSQLDeclarationReturnsValueFromPlatform()
    {
        $this->platform->getVarcharTypeDeclarationSQL(Argument::cetera())->willReturn('declaration');

        EnumerationBridge::registerEnumType(Gender::class, function ($value) {
            if (null !== $gender = Gender::memberOrNullByValue($value)) {
                return $gender;
            }

            throw new \InvalidArgumentException(sprintf('"%s" not valid for "%s"', $value, Gender::class));
        });

        $type = Type::getType(Gender::class);
        $this->assertEquals('declaration', $type->getSQLDeclaration([], $this->platform->reveal()));
    }

    /**
     * @test
     */
    public function convertToDatabaseValueParsesEnum()
    {
        EnumerationBridge::registerEnumType(Action::class, function ($value) {
            if (Action::isValid($value)) {
                return new Action($value);
            }

            throw new \InvalidArgumentException(sprintf('"%s" not valid for "%s"', $value, Action::class));
        });

        $type  = Type::getType(Action::class);

        $value = Action::CREATE();
        $this->assertEquals(Action::CREATE, $type->convertToDatabaseValue($value, $this->platform->reveal()));

        $value = Action::READ();
        $this->assertEquals(Action::READ, $type->convertToDatabaseValue($value, $this->platform->reveal()));

        $value = Action::UPDATE();
        $this->assertEquals(Action::UPDATE, $type->convertToDatabaseValue($value, $this->platform->reveal()));

        $value = Action::DELETE();
        $this->assertEquals(Action::DELETE, $type->convertToDatabaseValue($value, $this->platform->reveal()));
    }

    /**
     * @test
     */
    public function convertToPHPValueWithValidValueReturnsParsedData()
    {
        EnumerationBridge::registerEnumType(Action::class, function ($value) {
            if (Action::isValid($value)) {
                return new Action($value);
            }

            throw new \InvalidArgumentException(sprintf('"%s" not valid for "%s"', $value, Action::class));
        });

        $type = Type::getType(Action::class);

        /** @var Action $value */
        $value = $type->convertToPHPValue(Action::CREATE, $this->platform->reveal());
        $this->assertInstanceOf(Action::class, $value);
        $this->assertEquals(Action::CREATE, $value->getValue());

        $value = $type->convertToPHPValue(Action::DELETE, $this->platform->reveal());
        $this->assertInstanceOf(Action::class, $value);
        $this->assertEquals(Action::DELETE, $value->getValue());
    }

    /**
     * @test
     */
    public function convertToPHPValueWithNullReturnsNull()
    {
        EnumerationBridge::registerEnumType(Action::class, new Constructor());

        $type = Type::getType(Action::class);
        $value = $type->convertToPHPValue(null, $this->platform->reveal());
        $this->assertNull($value);
    }

    /**
     * @test
     */
    public function convertToPHPValueWithNullValuesSupported()
    {
        EnumerationBridge::registerEnumType(NullableType::class, new NullableConstructor());

        $type = Type::getType(NullableType::class);

        /** @var NullableType $value */
        $value = $type->convertToPHPValue(null, $this->platform->reveal());

        $this->assertInstanceOf(NullableType::class, $value);
        $this->assertNull($value->value());
    }

    /**
     * @test
     */
    public function convertToPHPValueWithInvalidValueThrowsException()
    {
        EnumerationBridge::registerEnumType(Action::class, function ($value) {
            if (Action::isValid($value)) {
                return new Action($value);
            }

            throw new \InvalidArgumentException(sprintf('"%s" not valid for "%s"', $value, Action::class));
        });

        $type = Type::getType(Action::class);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('"%s" not valid for "%s"', 'invalid', Action::class));
        $type->convertToPHPValue('invalid', $this->platform->reveal());
    }

    /**
     * @test
     */
    public function usingChildEnumTypeRegisteredValueIsCorrect()
    {
        MyType::registerEnumType(Action::class, function ($value) {
            if (Action::isValid($value)) {
                return new Action($value);
            }

            throw new \InvalidArgumentException(sprintf('"%s" not valid for "%s"', $value, Action::class));
        });

        $type = Type::getType(Action::class);
        $this->assertInstanceOf(MyType::class, $type);
        $this->assertEquals('FOO BAR', $type->getSQLDeclaration([], $this->platform->reveal()));
    }
}
