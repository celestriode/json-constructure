<?php namespace Celestriode\JsonConstructure\Structures;

use Celestriode\Constructure\InputInterface;
use Celestriode\Constructure\Reports\ReportsInterface;
use Celestriode\Constructure\Statistics\Statistics;
use Celestriode\Constructure\Reports\Message;
use Celestriode\Constructure\Reports\PrettifySupplier;

/**
 * Parent class for all scalar Json datatypes.
 * 
 * This includes booleans, integers, doubles, strings, and nulls.
 * 
 * Primarily just acts as a container for the value itself and reduces need for
 * duplicate code.
 * 
 * Note that this differs from the JsonScalar class, which is a standard
 * implementation of the JsonMixed that makes use of all scalar values,
 * making it simpler to create a scalar structure.
 */
abstract class AbstractScalar extends AbstractJson
{
    /** @var mixed $value The value of the scalar Json. */
    private $value;

    public function __construct($value = null)
    {
        $this->setValue($value);

        parent::__construct();
    }

    /**
     * Returns the value of this scalar field.
     *
     * @return void
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set the value of this Json.
     *
     * @param mixed $value The value to set. Hooray for extensive and descriptive documentation.
     * @return void
     */
    public function setValue($value = null): void
    {
        $this->value = $value;
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
        return (string)json_encode($this->getValue());
    }

    /**
     * Simply adds the data type to statistics.
     *
     * @param Statistics $statistics The statistics to manipulate.
     * @return void
     */
    public function addContextToStats(Statistics $statistics): void
    {
        $statistics->addStat(1, 'values', $this->getTypeName(), (string)$this->getValue());

        parent::addContextToStats($statistics);
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
        // Add context to statistics.

        $input->getContext()->addContextToStats($statistics);

        // If the expected structure actually has a value (which should be a rare event), check if it matches the input's value.

        if ($this->getValue() !== null && $input->getValue() !== $this->getValue()) {

            $reports->addReport(Message::warn($input->getContext(), 'Value %s does not match the expected value %s', (string)$input->getValue(), (string)$this->getValue()));

            return false;
        }

        // If no issues, return true.

        return true;
    }
}