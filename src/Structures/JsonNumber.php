<?php namespace Celestriode\JsonConstructure\Structures;

/**
 * A Json integer+double structure.
 */
class JsonNumber extends AbstractScalar
{
    /**
     * Returns the numerical datatype as a bitfield. Available types include: any, integer, double, boolean, string, array, object, null, number (integer/double), scalar (integer/double/boolean/string).
     *
     * @return integer
     */
    public function getType(): int
    {
        return self::NUMBER;
    }

    /**
     * Returns the name of the datatype of this class. Used for display and errors.
     *
     * @return string
     */
    public function getTypeName(): string
    {
        return 'integer, double';
    }
}
