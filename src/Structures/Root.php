<?php namespace Celestriode\JsonConstructure\Structures;

use Celestriode\Constructure\Reports\PrettifySupplier;
use Celestriode\Constructure\Statistics\Statistics;
use Celestriode\Constructure\InputInterface;
use Celestriode\Constructure\Reports\ReportsInterface;

/**
 * The root structure that all Json should start with.
 *
 * Not really required, but JsonConstructure.validateFromString() will
 * make use of it.
 */
final class Root extends AbstractJson
{
    /** @var AbstractJson $rootType The Json that represents the root. */
    private $rootType;

    public function __construct(AbstractJson $rootType)
    {
        $this->setRootType($rootType);

        parent::__construct();
    }

    /**
     * Returns the Json that represents the root.
     *
     * @return AbstractJson
     */
    public function getRootType(): AbstractJson
    {
        return $this->rootType;
    }

    /**
     * Returns the numerical datatype as a bitfield. Available types include: any, integer, double, boolean, string, array, object, null, number (integer/double), scalar (integer/double/boolean/string).
     *
     * @return integer
     */
    public function getType(): int
    {
        return self::ROOT;
    }

    /**
     * Returns the name of the datatype of this class. Used for display and errors.
     *
     * @return string
     */
    public function getTypeName(): string
    {
        return 'root';
    }

    /**
     * Sets the parent JSON of this one.
     *
     * @param AbstractJson $parent The parent of this input.
     * @return void
     */
    public function setParentInput(AbstractJson $parent = null): void
    {
        if ($parent !== null) {
            throw new \LogicException('Root cannot have a parent');
        }
    }

    /**
     * Sets the direct child of the root.
     *
     * @param AbstractJson $rootType The Json that represents the root.
     * @return void
     */
    public function setRootType(AbstractJson $rootType): void
    {
        $this->rootType = $rootType;
        $rootType->setParentInput($this);
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
        return $this->rootType->contextToString($prettifySupplier);
    }

    /**
     * Simply adds the data type to statistics.
     *
     * @param Statistics $statistics The statistics to manipulate.
     * @return void
     */
    public function addContextToStats(Statistics $statistics): void
    {
        $statistics->addStat(1, 'root', 'type', $this->rootType->getTypeName());
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
        $input->getContext()->addContextToStats($statistics);

        return $this->getRootType()->compareStructure($input->getRootType(), $reports, $statistics);
    }
}
