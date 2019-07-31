<?php namespace Celestriode\JsonConstructure\Structures;

use Celestriode\Constructure\Reports\PrettifySupplier;

/**
 * A Json boolean+integer+double+string structure.
 */
class JsonScalar extends AbstractScalar
{
    /**
     * Returns the numerical datatype as a bitfield. Available types include: any, integer, double, boolean, string, array, object, null, number (integer/double), scalar (integer/double/boolean/string).
     *
     * @return integer
     */
    public function getType(): int
    {
        return self::SCALAR;
    }

    /**
     * Returns the name of the datatype of this class. Used for display and errors.
     *
     * @return string
     */
    public function getTypeName(): string
    {
        return 'boolean, integer, double, string';
    }

    /**
     * Turns the context into a string for whatever display purposes necessary.
     *
     * This should be possible for any context because the context is meant to
     * be a structure to validate, and thus should have a string representation
     * for the user to see.
     *
     * @param PrettifySupplier $prettifySupplier Optional function to prettify data with.
     * @return string
     */
    public function contextToString(PrettifySupplier $prettifySupplier = null): string
    {
        if ($this->getValue() !== null) {

            return parent::contextToString($prettifySupplier);
        }

        return 'SCALAR';
    }
}