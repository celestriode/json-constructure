<?php namespace Celestriode\JsonConstructure\Structures;

use Celestriode\Constructure\Reports\PrettifySupplierInterface;
use Celestriode\Constructure\Statistics\Statistics;
use Celestriode\Constructure\Reports\ReportsInterface;
use Celestriode\Constructure\Reports\Message;
use Celestriode\Constructure\Predicates\PredicateInterface;
use Celestriode\JsonConstructure\Exceptions\MissingKey;
use Celestriode\Constructure\Utils\MessageUtils;
use Celestriode\JsonConstructure\Utils\JsonPrettifier;

/**
 * A Json object structure.
 */
class JsonObject extends AbstractJson
{
    /** @var array $fields The list of fields to expect. */
    private $fields = [];
    /** @var array $branches Optional list of branches that can add to the fields during validation. */
    private $branches = [];

    public function __construct(Field ...$fields)
    {
        foreach ($fields as $field) {
            $this->setField($field);
        }

        parent::__construct();
    }

    /**
     * Returns the numerical datatype as a bitfield. Available types include: any, integer, double, boolean, string, array, object, null, number (integer/double), scalar (integer/double/boolean/string).
     *
     * @return integer
     */
    public function getType(): int
    {
        return self::OBJECT;
    }

    /**
     * Returns the name of the datatype of this class. Used for display and errors.
     *
     * @return string
     */
    public function getTypeName(): string
    {
        return 'object';
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
        
        if ($prettifySupplier instanceof JsonPrettifier && $this->getRawInput() instanceof \stdClass) {
            return $prettifySupplier->prettifyObject($this->getRawInput(), $this);
        }

        // Otherwise just do basic JSON encoding.

        return json_encode($this->getRawInput());
    }

    /**
     * Add a field with the key provided via the field.
     *
     * If a field with the key already existed in the object,
     * it will be overridden.
     *
     * @param Field $field The field to add.
     * @return self
     */
    public function setField(Field $field): self
    {
        $this->fields[$field->getKey()] = $field;
        $field->getJson()->setParentInput($this);

        return $this;
    }

    /**
     * Returns whether or not the object had a field with
     * the specified key.
     *
     * @param string $key The key to look for.
     * @return boolean
     */
    public function hasField(string $key): bool
    {
        return array_key_exists($key, $this->getFields());
    }

    /**
     * Returns a field with the given key.
     *
     * If no such field exists, an error is thrown.
     *
     * @param string $key The key of the field to return.
     * @return Field
     */
    public function getField(string $key): Field
    {
        // If the field is missing in the expected structure, throw an error.

        if (!$this->hasField($key)) {
            throw MissingKey::create(Message::error($this->getContext(), 'Could not locate key %s', MessageUtils::key($key)));
        }

        // Otherwise return that field.

        return $this->getFields()[$key];
    }

    /**
     * Return all fields in the object.
     *
     * @return array
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Return all branches in the object.
     *
     * @return array
     */
    public function getBranches(): array
    {
        return $this->branches;
    }

    /**
     * Adds a branch to the object.
     *
     * @param string $label The label for the branch.
     * @param PredicateInterface $predicate The predicate that must succeed for the branch to be used.
     * @param Field ...$outcomes The fields that will be added to the object during validation if the branch succeeds.
     * @return self
     */
    public function addBranch(string $label, PredicateInterface $predicate, Field ...$outcomes): self
    {
        $this->branches[] = new Branch($label, $predicate, ...$outcomes);

        return $this;
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
        // Add object to statistics (but not its children).

        $input->getContext()->addContextToStats($statistics);

        $allSucceeds = true;

        // Check if any branches succeed. If so, they should be included within the list of fields.

        $fields = array_merge($this->getFields(), $this->getSuccessfulBranchFields($input, $reports, $statistics));
        $unexpectedFields = array_keys($input->getFields());
        $placeholders = [];

        // Cycle through all expected fields and make sure the input has them.

        /** @var Field $field */
        foreach ($fields as $key => $field) {

            // If it's a placeholder, store it for deferred action.

            if ($field->isPlaceholder()) {
                $placeholders[] = $field;

                continue;
            }

            // If it's not a placeholder, do normal checks.

            if ($field->isRequired() && !$input->hasField($key)) {

                // Bad if it's required and not specified.

                if ($input->getContainingField() !== null) {

                    // Error for contained input.

                    $input->addStructureReport(Message::error($input->getContext(), 'Missing required nested field %s for object %s', MessageUtils::key($key), MessageUtils::key($input->getContainingField()->getKey())), $reports);
                } else {

                    // Error for uncontained input.

                    $input->addStructureReport(Message::error($input->getContext(), 'Missing required field %s', MessageUtils::key($key)), $reports);
                }

                $allSucceeds = false;
            } elseif ($input->hasField($key) && !$field->getJson()->compareStructure($input->getField($key)->getJson(), $reports, $statistics)) {

                // Bad if the found structure failed to compare.

                $allSucceeds = false;
            }

            // Since $unexpectedFields begins will all of the input's fields, remove it when successful.

            if (($index = array_search($key, $unexpectedFields)) !== false) {

                // Also add to stats.

                $statistics->addStat(1, 'fields', $input->getField($key)->getJson()->getTypeName());
                $statistics->addStat(1, 'keys', $key);
                unset($unexpectedFields[$index]);
            }
        }

        // Handle placeholders.

        /** @var Field $placeholder */
        foreach ($placeholders as $placeholder) {
            foreach ($unexpectedFields as $index => $inputFieldKey) {

                /** @var Field $inputField */
                $inputField = $input->getField($inputFieldKey);

                // If the datatype matches this placeholder...

                if ($inputField->getJson()->isType($placeholder->getJson()->getType())) {
                    unset($unexpectedFields[$index]);

                    // Check if the placeholder's structure matches.

                    if (!$placeholder->getJson()->compareStructure($inputField->getJson(), $reports, $statistics)) {
                        $allSucceeds = false;
                    }
                }
            }
        }

        // Run a helper function to remove globally-accepted keys, such as "__comment".

        $this->removeGlobalKeys($unexpectedFields, $input, $reports, $statistics);

        // Add report if there were unexpected keys.

        if (!empty($unexpectedFields)) {
            $input->addStructureReport(Message::warn($input->getContext(), 'Unexpected keys found (%s); accepted keys: %s', MessageUtils::key(...$unexpectedFields), MessageUtils::key(...array_keys($fields))), $reports);
            
            $allSucceeds = false;
        }

        // Return whether all succeeds and if all audits were successful.

        return $allSucceeds;
    }

    /**
     * Takes an array of string keys and removes keys that should not
     * cause validation errors.
     *
     * By default this process is done after validating everything and
     * thus the keys provided are fields that the input has that could
     * not be found in the expected structure.
     *
     * @param array $keys The array of keys to modify directly.
     * @param AbstractJson $input The input, useful for error messages.
     * @param ReportsInterface $reports Reports to add to.
     * @param Statistics $statistics Statistics to manipulate. Not done by default (yet).
     * @return void
     */
    protected function removeGlobalKeys(array &$keys, AbstractJson $input, ReportsInterface $reports, Statistics $statistics): void
    {
        $ignoredKeys = [];

        // Cycle through each global key.

        foreach ($this->getGlobalKeys($keys) as $key) {

            // If the global key existed in the list of keys...

            if (($index = array_search($key, $keys)) !== false) {

                // Save that key for a report and remove it from the original array.

                $ignoredKeys[] = $key;
                unset($keys[$index]);
            }
        }

        // If the list of ignored keys isn't empty...

        if (!empty($ignoredKeys)) {

            // Add a report.

            $input->addStructureReport(Message::info($input->getContext(), 'Ignoring globally-accepted keys: %s', MessageUtils::key(...$ignoredKeys)), $reports);
        }
    }

    /**
     * Returns a list of ignored keys.
     *
     * Takes in an array of keys that it can use to
     * generate a list of global keys, such as using
     * regex to find any key that contains "comment"
     * among any other character (such as underscores).
     *
     * @param array $keys Optional array of original keys to generate global keys from.
     * @return array
     */
    protected function getGlobalKeys(array $keys = []): array
    {
        $buffer = [];

        // Cycle through each of the incoming keys.

        foreach ($keys as $key) {

            // If the key contains the word "comment", add that key to the list.

            if (stripos($key, 'comment') !== false) {
                $buffer[] = $key;
            }
        }

        // Return the completed buffer.

        return $buffer;
    }

    /**
     * Returns an array of branches that succeed.
     *
     * The outcome of these branches will be added to the object's list
     * of fields during validation.
     *
     * @param AbstractJson $input The input to use for testing.
     * @param ReportsInterface $reports Reports to add to if successfully branched.
     * @param Statistics $statistics Statistics to manipulate.
     * @return array
     */
    private function getSuccessfulBranchFields(AbstractJson $input, ReportsInterface $reports, Statistics $statistics): array
    {
        $buffer = [];

        // Cycle through all branches.

        /** @var Branch $branch */
        foreach ($this->getBranches() as $branch) {

            // Test if the branch's predicate succeeds.

            if ($branch->succeeds($input, $reports, $statistics)) {

                // If so, cycle through all outcome fields and add them to the buffer.

                $input->addStructureReport(Message::debug($input->getContext(), 'Successfully branched to: %s', MessageUtils::key($branch->getLabel())), $reports);

                /** @var Field $field */
                foreach ($branch->getOutcomes() as $field) {
                    $buffer[$field->getKey()] = $field;
                }
            }
        }

        // Return the list of successful branch structures.

        return $buffer;
    }
}
