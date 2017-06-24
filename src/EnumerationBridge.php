<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license.
 */

namespace Somnambulist\DoctrineEnumBridge;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;

/**
 * Class EnumerationBridge
 *
 * This class is based on PhpEnumType from acelaya\doctrine-enum-type:
 * @link https://github.com/acelaya/doctrine-enum-type/blob/master/src/Type/PhpEnumType.php
 *
 * @package    Somnambulist\DoctrineEnumBridge
 * @subpackage Somnambulist\DoctrineEnumBridge\EnumerationBridge
 */
class EnumerationBridge extends Type
{

    /**
     * The enumerable instance name
     *
     * @var string
     */
    private $name;

    /**
     * A callable that can build the PHP type from the value
     *
     * @var callable
     */
    private $constructor;

    /**
     * An optional callable that can serializer the enumerable to a string (default casts to string)
     *
     * @var callable
     */
    private $serializer;

    /**
     * Register a set of types with the provided constructor and serializer callables
     *
     * @param array $types An Array of name => constructor or alias => [ constructor, serializer ]
     *
     * @throws InvalidArgumentException
     * @throws DBALException
     */
    public static function registerEnumTypes(array $types = [])
    {
        foreach ($types as $name => $callbacks) {
            [$constructor, $serializer] = array_merge((array)$callbacks, [null]);

            static::registerEnumType($name, $constructor, $serializer);
        }
    }

    /**
     * Registers an enumerable handler
     *
     * @param string   $name
     * @param callable $constructor Receives: value, enum name, platform
     * @param callable $serializer  Receives: value, enum name, platform
     *
     * @throws InvalidArgumentException
     * @throws DBALException
     */
    public static function registerEnumType($name, callable $constructor, callable $serializer = null)
    {
        if (static::hasType($name)) {
            return;
        }

        $serializer = $serializer ?? function ($value) { return ($value === null) ? null : (string)$value; };

        // Register and customize the type
        static::addType($name, static::class);

        /** @var EnumerationBridge $type */
        $type              = static::getType($name);
        $type->name        = $name;
        $type->constructor = $constructor;
        $type->serializer  = $serializer;
    }

    /**
     * Gets the name of this type.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name ?: 'enum';
    }

    /**
     * Gets the SQL declaration snippet for a field of this type.
     *
     * @param array            $fieldDeclaration The field declaration.
     * @param AbstractPlatform $platform         The currently used database platform.
     *
     * @return string
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getVarcharTypeDeclarationSQL([]);
    }

    /**
     * @param string           $value
     * @param AbstractPlatform $platform
     *
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        return ($this->constructor)($value, $this->name, $platform);
    }

    /**
     * @param mixed            $value
     * @param AbstractPlatform $platform
     *
     * @return null|string
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return ($this->serializer)($value, $this->name, $platform);
    }
}
