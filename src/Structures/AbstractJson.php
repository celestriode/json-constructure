<?php namespace Celestriode\JsonConstructure\Structures;

use Celestriode\Constructure\AbstractExpectedStructure;
use Celestriode\Constructure\Reports\ContextInterface;
use Celestriode\Constructure\InputInterface;
use Celestriode\Constructure\Reports\ReportsInterface;
use Celestriode\Constructure\Reports\Message;
use Celestriode\Constructure\Statistics\Statistics;
use Ramsey\Uuid\UuidInterface;
use Celestriode\Constructure\Utils\StructureReportsTrait;
use Celestriode\Constructure\Utils\MessageUtils;

/**
 * The parent class of all Json classes.
 */
abstract class AbstractJson extends AbstractExpectedStructure implements InputInterface, ContextInterface
{
    use StructureReportsTrait;

    const ANY = -1;
    const INTEGER = 1;
    const DOUBLE = 2;
    const BOOLEAN = 4;
    const STRING = 8;
    const ARRAY = 16;
    const OBJECT = 32;
    const NULL = 64;
    const ROOT = 128;
    const NUMBER = self::INTEGER | self::DOUBLE;
    const SCALAR = self::NUMBER | self::BOOLEAN | self::STRING;

    /** @var mixed $rawInput The input before being turned into AbstractJson. */
    private $rawInput;
    /** @var array $uuids A mapping of (string)UUID => Json. */
    private static $uuids = [];
    /** @var bool $nullable Whether or not this structure may be null. */
    private $nullable = false;
    /** @var Field|null $containingField The field that this Json might be contained within. */
    private $containingField;
    /** @var int|null $arrayIndex The index that this Json would be at if the parent input is an array. */
    private $arrayIndex;
    /** @var AbstractJson|null $parent The parent structure of this one, if applicable. */
    private $parent;

    /**
     * Returns the numerical datatype as a bitfield. Available types include: any, integer, double, boolean, string, array, object, null, number (integer/double), scalar (integer/double/boolean/string).
     *
     * @return integer
     */
    abstract public function getType(): int;

    /**
     * Returns the name of the datatype of this class. Used for display and errors.
     *
     * @return string
     */
    abstract public function getTypeName(): string;

    /**
     * Custom comparison method, called by AbstractJson.compareStructure().
     *
     * @param AbstractJson $input The input to compare with the structure.
     * @param ReportsInterface $reports Reports to add messages to.
     * @param Statistics $statistics Statistics to manipulate.
     * @return boolean
     */
    abstract public function compareJsonStructure(AbstractJson $input, ReportsInterface $reports, Statistics $statistics): bool;

    /**
     * Sets the raw input of this Json.
     *
     * @param mixed $rawInput The input to set.
     * @return void
     */
    final public function setRawInput($rawInput): void
    {
        $this->rawInput = $rawInput;
    }

    /**
     * Returns the raw input of this Json.
     *
     * @return mixed
     */
    final public function getRawInput()
    {
        return $this->rawInput;
    }

    /**
     * Sets the field that this Json is contained within.
     *
     * @param Field $containingField The field containing the Json.
     * @return void
     */
    final public function setContainingField(Field $containingField = null): void
    {
        $this->containingField = $containingField;
    }

    /**
     * Returns the field that this Json might be contained within.
     *
     * @return Field|null
     */
    final public function getContainingField(): ?Field
    {
        return $this->containingField;
    }

    /**
     * Sets the array index that this Json would have if the parent input
     * is an array.
     *
     * @param integer $arrayIndex The index within the parent array.
     * @return void
     */
    public function setArrayIndex(int $arrayIndex = null): void
    {
        $this->arrayIndex = $arrayIndex;
    }

    /**
     * Returns the array index that this Json would have if it's in an array.
     *
     * Returns null if it does not have a index.
     *
     * @return integer|null
     */
    final public function getArrayIndex(): ?int
    {
        return $this->arrayIndex;
    }

    /**
     * Compares the input to the expected structure.
     *
     * @param InputInterface $input The input to compare with the structure.
     * @param ReportsInterface $reports Reports to add messages to.
     * @param Statistics $statistics Statistics to manipulate.
     * @return boolean
     */
    public function compareStructure(InputInterface $input, ReportsInterface $reports, Statistics $statistics): bool
    {
        // If the input is null and the value is allowed to be null, skip silently.

        if ($input instanceof JsonNull && $this->isNullable()) {
            return true;
        }

        // Skip if it's either the wrong class or is Json but not the correct datatype.

        if (!$this->isCorrectType($input, $reports)) {
            return false;
        }

        // Run structure comparison.

        $result = $this->compareJsonStructure($input, $reports, $statistics);

        // Perform audits and return true as long as both of them are correct.

        return $this->performAudits($input, $reports, $statistics) && $result;
    }

    /**
     * Returns the Json based on the provided UUID. If none existed, \RunetimeException is thrown.
     *
     * Use setUuid() to apply a UUID to a structure.
     *
     * @param UuidInterface $targetUuid The UUID of the Json to locate.
     * @return self
     */
    public static function getJsonByUuid(UuidInterface $targetUuid): self
    {
        // Throw if the UUID isn't stored.

        if (!isset(self::$uuids[$targetUuid->toString()])) {
            throw new \RuntimeException('There was no Json stored with the UUID "' . $targetUuid->toString() . '"');
        }

        // Otherwise return the Json belonging to the UUID.

        return self::$uuids[$targetUuid->toString()];
    }

    /**
     * Sets the UUID of the structure. This is primarily used to reference the expected structure,
     * not the input. For example, the JsonRedirect class makes use of UUIDs to continue validation
     * from a different part of the structure.
     *
     * @param UuidInterface $uuid The UUID to apply to the structure.
     * @return self
     */
    public function setUuid(UuidInterface $uuid): self
    {
        self::$uuids[$uuid->toString()] = $this;

        return $this;
    }

    /**
     * The context is itself in this case, as it implements the necessary methods.
     *
     * @return ContextInterface
     */
    public function getContext(): ContextInterface
    {
        return $this;
    }

    /**
     * Simply adds the data type to statistics.
     *
     * @param Statistics $statistics The statistics to manipulate.
     * @return void
     */
    public function addContextToStats(Statistics $statistics): void
    {
        $statistics->addStat(1, 'types', $this->getTypeName());
    }

    /**
     * Returns whether or not the incoming type matches the type of this structure.
     *
     * @param integer $type The type to match with.
     * @return boolean
     */
    public function isType(int $type): bool
    {
        return ($this->getType() & $type) !== 0;
    }

    /**
     * Returns the parent JSON of this one, if applicable.
     *
     * @return InputInterface|null
     */
    public function getParentInput(): ?InputInterface
    {
        return $this->parent;
    }

    /**
     * Sets the parent JSON of this one.
     *
     * @param AbstractJson $parent The parent of this input.
     * @return void
     */
    public function setParentInput(AbstractJson $parent = null): void
    {
        $this->parent = $parent;
    }

    /**
     * Sets the structure as allowing or disallowing null values.
     *
     * This is prevelant with fields but done on the Json-level.
     *
     * By default, structures will not accept null values.
     *
     * @param boolean $nullable Whether or not the structure can be null.
     * @return self
     */
    public function nullable(bool $nullable = true): self
    {
        $this->nullable = $nullable;

        return $this;
    }

    /**
     * Returns whether or not this structure can be null.
     *
     * @return boolean
     */
    public function isNullable(): bool
    {
        return $this->nullable;
    }

    /**
     * Simple type verification. Checks if the input is Json and then
     * checks if the input is the correct expected class (like Root
     * or JsonString).
     *
     * @param InputInterface $input The input to check the type of.
     * @param ReportsInterface $reports The reports to add to.
     * @return boolean
     */
    protected function isCorrectType(InputInterface $input, ReportsInterface $reports): bool
    {
        // If the input isn't Json, bad.

        if (!($input instanceof AbstractJson)) {
            throw new \InvalidArgumentException('Inputs for comparison can only be AbstractJson classes.');
        }

        // If the input is Json but isn't the correct datatype, bad.

        if (!($input->isType($this->getType()))) {
            if ($input->getContainingField() !== null) {

                // Error for contained input.

                $input->addStructureReport(Message::error($input->getContext(), 'Invalid type %s for field %s, should instead be %s', MessageUtils::key($input->getTypeName()), MessageUtils::key($input->getContainingField()->getKey()), MessageUtils::key($this->getTypeName())), $reports);
            } else {

                // Error for uncontained input.

                $input->addStructureReport(Message::error($input->getContext(), 'Invalid type %s, should instead be %s', MessageUtils::key($input->getTypeName()), MessageUtils::key($this->getTypeName())), $reports);
            }

            return false;
        }

        // Otherwise all good, return true.

        return true;
    }
}
