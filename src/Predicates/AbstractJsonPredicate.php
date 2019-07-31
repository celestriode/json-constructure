<?php namespace Celestriode\JsonConstructure\Predicates;

use Celestriode\Constructure\InputInterface;
use Celestriode\JsonConstructure\Structures\AbstractJson;
use Celestriode\Constructure\Predicates\AbstractPredicate;

/**
 * Parent class for all JsonConstructure predicates.
 */
abstract class AbstractJsonPredicate extends AbstractPredicate
{
    /**
     * Returns whether or not the input is the correct structure.
     *
     * In this context, the InputInterface must extend AbstractJson.
     *
     * @param InputInterface $input The input to validate.
     * @return boolean
     */
    protected function isCorrectStructure(InputInterface $input): bool
    {
        return $input instanceof AbstractJson;
    }
}
