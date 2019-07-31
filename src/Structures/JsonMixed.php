<?php namespace Celestriode\JsonConstructure\Structures;

use Celestriode\JsonConstructure\Utils\Json;
use Celestriode\Constructure\Reports\PrettifySupplierInterface;
use Celestriode\Constructure\Reports\ReportsInterface;
use Celestriode\Constructure\Statistics\Statistics;
use Celestriode\Constructure\Reports\Message;
use Celestriode\Constructure\Utils\MessageUtils;

/**
 * A Json structure of varying type.
 *
 * Use this for odd combos. Don't use this for preset combos like:
 *
 * JsonNumber
 * JsonScalar
 */
class JsonMixed extends AbstractJson
{
    /** @var array $acceptedJson Array of acceptable values. */
    private $acceptedJson = [];

    public function __construct(AbstractJson ...$acceptedJson)
    {
        $this->addAcceptedJson(...$acceptedJson);

        parent::__construct();
    }

    /**
     * Returns the list of accepted Json values.
     *
     * @return array
     */
    public function getAcceptedJson(): array
    {
        return $this->acceptedJson;
    }

    /**
     * Appends to the list of accepted data.
     *
     * @param AbstractJson ...$acceptedJson The accepted data to append with.
     * @return void
     */
    public function addAcceptedJson(AbstractJson ...$acceptedJson): void
    {
        // Cycle through each of the incoming accepted Json.

        foreach ($acceptedJson as $json) {

            // Append to the list and set the parent of the incoming Json to this Json.

            $this->acceptedJson[] = $json;
            $json->setParentInput($this->getParentInput());
        }
    }

    /**
     * Returns the numerical datatype as a bitfield. Available types include: any, integer, double, boolean, string, array, object, null, number (integer/double), scalar (integer/double/boolean/string).
     *
     * @return integer
     */
    public function getType(): int
    {
        return Json::combineTypes(...$this->acceptedJson);
    }

    /**
     * Returns the name of the datatype of this class. Used for display and errors.
     *
     * @return string
     */
    public function getTypeName(): string
    {
        return Json::combineTypeNames(...$this->acceptedJson);
    }

    /**
     * Turns the context into a string for whatever display purposes necessary.
     *
     * This should be possible for any context because the context is meant to
     * be a structure to validate, and thus should have a string representation
     * for the user to see.
     *
     * @param PrettifySupplierInterface $prettifySupplier Optional function to prettify data with.
     * @return string
     */
    public function contextToString(PrettifySupplierInterface $prettifySupplier = null): string
    {
        $buffer = '%(';

        // Cycle through each accepted Json.

        for ($i = 0, $j = count($this->getAcceptedJson()); $i < $j; $i++) {

            // Add the accepted Json to the buffer.

            $buffer .= '<<';
            $buffer .= $this->getAcceptedJson()[$i]->contextToString($prettifySupplier);
            $buffer .= '>>';

            // If there's more accepted Json, add a comma.

            if ($i + 1 < $j) {
                $buffer .= ',';
            }
        }

        // Return the completed buffer.

        return $buffer . ')%';
    }

    /**
     * Custom comparison method, called by AbstractJson.compareStructure().
     *
     * @param AbstractJson $input The input to compare with the structure.
     * @param ReportsInterface $reports Reports to add messages to.
     * @param Statistics $statistics Statistics to manipulate.
     * @return boolean
     */
    public function compareJsonStructure(AbstractJson $input, ReportsInterface $reports, Statistics $statistics): bool
    {
        // Cycle through each accepted Json datatype.

        foreach ($this->getAcceptedJson() as $json) {

            // If the input matched the datatype of the Json...

            if ($json->isType($input->getType())) {

                // Return whether or not it matched that accepted Json.
                
                return $json->compareStructure($input, $reports, $statistics);
            }
        }

        // Did not match any accept Json, add report and return false.

        if ($input->getContainingField() !== null) {

            // Error for contained input.

            $input->addStructureReport(Message::error($input->getContext(), 'Invalid type %s for field %s, must have been one of: %s', MessageUtils::key($input->getTypeName()), MessageUtils::key($input->getContainingField()->getKey()), MessageUtils::key($this->getTypeName())), $reports);
        } else {

            // Error for uncontained input.

            $input->addStructureReport(Message::error($input->getContext(), 'Invalid type %s, must have been one of: %s', MessageUtils::key($input->getTypeName()), MessageUtils::key($this->getTypeName())), $reports);
        }

        return false;
    }
}
