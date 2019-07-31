<?php namespace Celestriode\JsonConstructure\Utils;

use Celestriode\JsonConstructure\Structures\AbstractJson;
use Celestriode\JsonConstructure\Structures\Root;
use Celestriode\JsonConstructure\Structures\JsonObject;
use Celestriode\JsonConstructure\Structures\JsonString;
use Celestriode\JsonConstructure\Structures\JsonBoolean;
use Celestriode\JsonConstructure\Structures\JsonInteger;
use Celestriode\JsonConstructure\Structures\JsonDouble;
use Celestriode\JsonConstructure\Structures\JsonNull;
use Celestriode\JsonConstructure\Structures\JsonMixed;
use Celestriode\JsonConstructure\Structures\Field;
use Celestriode\JsonConstructure\Structures\JsonRedirect;
use Celestriode\JsonConstructure\Structures\JsonArray;
use Ramsey\Uuid\UuidInterface;
use Seld\JsonLint\JsonParser;

/**
 * Helper class to more easily create Json structures.
 *
 * Also contains a couple datatype-related helper methods.
 */
class Json
{
    /**
     * Turns a JSON string into a Json constructure.
     *
     * If there are parsing errors, they will not be caught.
     *
     * @param string $json The JSON to transform.
     * @return Root
     */
    public static function stringToStructure(string $json): Root
    {
        $parser = new JsonParser();
        $object = $parser->parse($json);

        return static::root(static::turnDataIntoStructure($object));
    }

    /**
     * Turns the data, whatever it may be, into an AbstractJson structure.
     *
     * @param mixed $data The data to transform into Json.
     * @return AbstractJson
     */
    protected static function turnDataIntoStructure($data): AbstractJson
    {
        // Handle primitive data.

        if ($data === null) {
            return new JsonNull();
        } elseif (is_string($data)) {
            return new JsonString($data);
        } elseif ($data === true || $data === false) {
            return new JsonBoolean($data);
        } elseif (is_double($data)) {
            return new JsonDouble($data);
        } elseif (is_integer($data)) {
            return new JsonInteger($data);
        }

        // Handle objects.

        if ($data instanceof \stdClass) {
            $object = new JsonObject();

            foreach ($data as $key => $value) {
                $object->setField(Field::key($key, static::turnDataIntoStructure($value)));
            }

            return $object;
        }

        // Handle arrays.

        if (is_array($data)) {
            $array = new JsonArray();

            foreach ($data as $element) {
                $array->addElements(static::turnDataIntoStructure($element));
            }

            return $array;
        }

        // Unknown data.

        throw new \InvalidArgumentException('Could not parse data');
    }

    /**
     * Takes in a list of Json and combines their types into a bitfield.
     *
     * @param AbstractJson ...$types The Json to combine types with.
     * @return integer
     */
    public static function combineTypes(AbstractJson ...$types): int
    {
        $type = 0;

        // Cycle through all types.

        foreach ($types as $json) {

            // Add their type to the field.

            $type = $type | $json->getType();
        }

        // Return the completed type.

        return $type;
    }

    /**
     * Takes in a list of Json and combines their type names into a single string.
     *
     * TODO: prevent duplicate names by adding both number() and scalar() to mixed().
     * Requires rewriting how type names are supplied.
     *
     * @param AbstractJson ...$json The Json to combine type names with.
     * @return string
     */
    public static function combineTypeNames(AbstractJson ...$json): string
    {
        $names = [];

        // Cycle through all types.

        foreach ($json as $type) {

            // Add their type name to the list.

            if (!in_array($type->getTypeName(), $names)) {
                $names[] = $type->getTypeName();
            }
        }

        // Implode and return the type names.

        return implode(', ', $names);
    }

    /**
     * Returns a new root structure.
     *
     * @param AbstractJson $json The Json represented by the root.
     * @return Root
     */
    public static function root(AbstractJson $json): Root
    {
        return new Root($json);
    }

    /**
     * Returns a new boolean structure.
     *
     * @param boolean $value The value of the boolean.
     * @return JsonBoolean
     */
    public static function boolean(bool $value = null): JsonBoolean
    {
        return new JsonBoolean($value);
    }

    /**
     * Returns a new integer structure.
     *
     * @param integer $value The value of the integer.
     * @return JsonInteger
     */
    public static function integer(int $value = null): JsonInteger
    {
        return new JsonInteger($value);
    }

    /**
     * Returns a new double structure.
     *
     * @param float $value The value of the double.
     * @return JsonDouble
     */
    public static function double(float $value = null): JsonDouble
    {
        return new JsonDouble($value);
    }

    /**
     * Returns a new number structure (integer & double).
     *
     * @param float $value The value of the number.
     * @return JsonMixed
     */
    public static function number(float $value = null): JsonMixed
    {
        return static::mixed(static::integer((int)$value), static::double($value));
    }

    /**
     * Returns a new string structure.
     *
     * @param string $value The value of the string.
     * @return JsonString
     */
    public static function string(string $value = null): JsonString
    {
        return new JsonString($value);
    }

    /**
     * Returns a new scalar structure (boolean & integer & double & string).
     *
     * @param mixed $value The value of the scalar.
     * @return JsonMixed
     */
    public static function scalar($value = null): JsonMixed
    {
        $null = $value === null;
        $bool = $null ? null : (bool)$value;
        $number = $null ? null : (float)$value;
        $string = $null ? null : (string)$value;

        return static::mixed(static::boolean($bool), static::number($number), static::string($string));
    }

    /**
     * Returns a new array structure.
     *
     * @param AbstractJson ...$json Elements within the array.
     * @return JsonArray
     */
    public static function array(AbstractJson ...$json): JsonArray
    {
        return new JsonArray(...$json);
    }

    /**
     * Returns a new object structure.
     *
     * @param Field ...$fields The fields within the object.
     * @return JsonObject
     */
    public static function object(Field ...$fields): JsonObject
    {
        return new JsonObject(...$fields);
    }

    /**
     * Returns a new null structure.
     *
     * @return JsonNull
     */
    public static function null(): JsonNull
    {
        return new JsonNull();
    }

    /**
     * Returns a new mixed structure.
     *
     * @param AbstractJson ...$types The accepted Json.
     * @return JsonMixed
     */
    public static function mixed(AbstractJson ...$types): JsonMixed
    {
        return new JsonMixed(...$types);
    }

    /**
     * Returns a new redirect structure.
     *
     * @param UuidInterface $target The Uuid of the target structure to redirect to.
     * @return JsonRedirect
     */
    public static function redirect(UuidInterface $target): JsonRedirect
    {
        return new JsonRedirect($target);
    }
}
