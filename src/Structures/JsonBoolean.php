<?php namespace Celestriode\JsonConstructure\Structures;

/**
 * A Json boolean structure.
 */
class JsonBoolean extends AbstractScalar
{
    /**
     * Returns the numerical datatype as a bitfield. Available types include: any, integer, double, boolean, string, array, object, null, number (integer/double), scalar (integer/double/boolean/string).
     *
     * @return integer
     */
    public function getType(): int
    {
        return self::BOOLEAN;
    }

    /**
     * Returns the name of the datatype of this class. Used for display and errors.
     *
     * @return string
     */
    public function getTypeName(): string
    {
        return 'boolean';
    }
}
