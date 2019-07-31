<?php namespace Celestriode\JsonConstructure\Structures;

use Celestriode\Constructure\Reports\PrettifySupplierInterface;
use Celestriode\Constructure\Reports\ReportsInterface;
use Celestriode\Constructure\Statistics\Statistics;
use Celestriode\Constructure\Reports\Message;
use Celestriode\Constructure\Utils\MessageUtils;
use Celestriode\JsonConstructure\Utils\JsonPrettifier;

/**
 * A Json array structure.
 *
 * Currently it is lenient with its validation. That is,
 * if all of the input's elements successfully matched
 * the expected structure's elements, but the expected
 * structure still had some extra elements to validate,
 * it will still pass.
 *
 * Maybe one day it'll have an option to be restrictive.
 */
class JsonArray extends AbstractJson
{
    /** @var array $elements The accepted Json within the array. */
    private $elements = [];

    public function __construct(AbstractJson ...$elements)
    {
        $this->addElements(...$elements);

        parent::__construct();
    }

    /**
     * Returns the numerical datatype as a bitfield. Available types include: any, integer, double, boolean, string, array, object, null, number (integer/double), scalar (integer/double/boolean/string).
     *
     * @return integer
     */
    public function getType(): int
    {
        return self::ARRAY;
    }

    /**
     * Returns the name of the datatype of this class. Used for display and errors.
     *
     * @return string
     */
    public function getTypeName(): string
    {
        return 'array';
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
        // If this Json belongs to a field, have the field do the work.

        if ($this->getContainingField() !== null) {
            return $this->getContainingField()->fieldToContextString($prettifySupplier);
        }

        // If a JsonPrettifier is supplied, use that to prettify.

        if ($prettifySupplier instanceof JsonPrettifier && is_array($this->getRawInput())) {
            return $prettifySupplier->prettifyArray($this->getRawInput(), $this);
        }

        // Otherwise just do basic JSON encoding.

        return json_encode($this->getRawInput());
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
        // Cycle through each element on the input.

        $anySucceeds = true;

        foreach ($input->getElements() as $index => $inputElement) {

            // Now cycle through all elements on the template to compare.

            $anySucceedsForElement = false;

            foreach ($this->getElements() as $template) {

                // If the datatype matches...

                if ($inputElement->isType($template->getType())) {
                    $anySucceedsForElement = true;
                    $statistics->addStat(1, 'elements', $inputElement->getTypeName());

                    // If the template failed to match, completely broken.

                    if (!$template->compareStructure($inputElement, $reports, $statistics)) {
                        $anySucceeds = false;
                    }
                }
            }

            // If the individual element failed to match any templates, add report.

            if (!$anySucceedsForElement) {
                if ($input->getContainingField() !== null) {

                    // Error for contained input.

                    $input->addStructureReport(Message::warn($inputElement->getContext(), 'Unexpected/invalid array element for array %s at position %s', MessageUtils::key($input->getContainingField()->getKey()), (string)$index), $reports);
                } else {

                    // Error for uncontained input.

                    $input->addStructureReport(Message::warn($inputElement->getContext(), 'Unexpected/invalid array element at position %s', (string)$index), $reports);
                }

                $anySucceeds = false;
            }
        }

        // Return as long as any succeed and audits succeed.

        return $anySucceeds;
    }

    /**
     * Appends accepted elements to this array's list of accepted elements.
     *
     * @param AbstractJson ...$elements The elements to append.
     * @return void
     */
    public function addElements(AbstractJson ...$elements): void
    {
        foreach ($elements as $element) {

            // Append the element and set its parent as the array.

            $this->elements[] = $element;
            $element->setParentInput($this);
            $element->setArrayIndex(count($this->elements) - 1);
        }
    }

    /**
     * Returns all the accepted elements that the input can have.
     *
     * @return array
     */
    public function getElements(): array
    {
        return $this->elements;
    }
}
