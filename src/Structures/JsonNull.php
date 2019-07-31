<?php namespace Celestriode\JsonConstructure\Structures;

use Celestriode\Constructure\Reports\ReportsInterface;
use Celestriode\Constructure\Statistics\Statistics;
use Celestriode\Constructure\Reports\PrettifySupplier;

/**
 * A Json null structure.
 */
class JsonNull extends AbstractJson
{
    /**
     * Returns the numerical datatype as a bitfield. Available types include: any, integer, double, boolean, string, array, object, null, number (integer/double), scalar (integer/double/boolean/string).
     *
     * @return integer
     */
    public function getType(): int
    {
        return self::NULL;
    }

    /**
     * Returns the name of the datatype of this class. Used for display and errors.
     *
     * @return string
     */
    public function getTypeName(): string
    {
        return 'null';
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
        return 'null';
    }

    /**
     * Custom comparison method, called by AbstractJson.compareStructure().
     *
     * @param InputInterface $input The input to compare with the structure.
     * @param ReportsInterface $reports Reports to add messages to.
     * @param Statistics $statistics Statistics to manipulate.
     * @return boolean
     */
    public function compareJsonStructure(AbstractJson $input, ReportsInterface $reports, Statistics $statistics): bool
    {
        return true;
    }
}