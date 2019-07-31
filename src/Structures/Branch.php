<?php namespace Celestriode\JsonConstructure\Structures;

use Celestriode\Constructure\Predicates\PredicateInterface;
use Celestriode\Constructure\Reports\ReportsInterface;
use Celestriode\Constructure\Statistics\Statistics;

/**
 * A container of fields that is used by JsonObject. If the
 * supplied predicate succeeds, then the fields are added to
 * the object for structural validation.
 *
 * This allows manipulation of the structure based on whatever
 * condition is needed.
 */
class Branch
{
    /** @var string $label The user-friendly label of the branch. */
    private $label;
    /** @var PredicateInterface $predicate The predicate that must succeed for the branch to be used. */
    private $predicate;
    /** @var array $outcomes An array of Fields that are added to the object should the branch succeed. */
    private $outcomes = [];

    public function __construct(string $label, PredicateInterface $predicate, Field ...$outcomes)
    {
        $this->setLabel($label);
        $this->setPredicate($predicate);
        $this->setOutcomes(...$outcomes);
    }

    /**
     * Returns the user-friendly label of the branch.
     *
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Returns the predicate that must pass for this branch to succeed.
     *
     * @return PredicateInterface
     */
    public function getPredicate(): PredicateInterface
    {
        return $this->predicate;
    }

    /**
     * Returns all the fields that the object will use if the branch succeeds.
     *
     * @return array
     */
    public function getOutcomes(): array
    {
        return $this->outcomes;
    }

    /**
     * Sets the user-friendly name of the branch. Use this for display.
     *
     * @param string $label The label of the branch.
     * @return void
     */
    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    /**
     * Sets the requirement for the branch to succeed.
     *
     * @param PredicateInterface $predicate The predicate that must pass.
     * @return void
     */
    public function setPredicate(PredicateInterface $predicate): void
    {
        $this->predicate = $predicate;
    }

    /**
     * Replaces all outcomes currently stored with the specified outcomes.
     *
     * @param Field ...$outcomes The outcomes to replace stored outcomes with.
     * @return void
     */
    public function setOutcomes(Field ...$outcomes): void
    {
        $this->outcomes = $outcomes;
    }

    /**
     * Adds a field to the list of fields that will become accessible
     * should this branch succeed.
     *
     * @param Field ...$outcomes The fields to add to the object.
     * @return void
     */
    public function addOutcomes(Field ...$outcomes): void
    {
        $this->setOutcomes(...array_merge($this->getOutcomes(), $outcomes));
    }

    /**
     * Returns whether or not the branch can actually be used.
     *
     * @param AbstractJson $input The input to test against.
     * @param ReportsInterface $reports Reports to optionally add to. Not done with the default Branch class.
     * @param Statistics $statistics Statistics to add to. Not done with the default Branch class (yet).
     * @return boolean
     */
    public function succeeds(AbstractJson $input, ReportsInterface $reports, Statistics $statistics): bool
    {
        return $this->getPredicate()->test($input);
    }
}
