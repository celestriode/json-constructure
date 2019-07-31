<?php namespace Celestriode\JsonConstructure\Structures;

use Ramsey\Uuid\UuidInterface;
use Celestriode\Constructure\Reports\PrettifySupplier;
use Celestriode\Constructure\Reports\ReportsInterface;
use Celestriode\Constructure\Statistics\Statistics;

/**
 * A placeholder class that takes in the Uuid of
 * a different expected structure. Once the input
 * reaches the redirect, the redirect will validate
 * using the structure of the Json that has the
 * corresponding Uuid.
 *
 * Use setUuid() on the structure you want to redirect to.
 */
class JsonRedirect extends AbstractJson
{
    /** @var UuidInterface $target */
    private $targetUuid;
    /** @var AbstractJson $target */
    private $target;

    public function __construct(UuidInterface $targetUuid)
    {
        $this->setTargetUuid($targetUuid);

        parent::__construct();
    }

    /**
     * Returns the target itself. May be null if it hasn't been loaded yet.
     *
     * @return AbstractJson|null
     */
    public function getTarget(): ?AbstractJson
    {
        return $this->target;
    }

    /**
     * Returns the target Uuid.
     *
     * @return UuidInterface
     */
    public function getTargetUuid(): UuidInterface
    {
        return $this->targetUuid;
    }

    /**
     * Sets the target of the redirect manually.
     *
     * @param AbstractJson $target The target of the redirect.
     * @return void
     */
    public function setTarget(AbstractJson $target): void
    {
        $this->target = $target;
    }

    /**
     * Sets the target Uuid of the redirect. Make sure to use setUuid() on the structure
     * that you want to redirect to.
     *
     * @param UuidInterface $targetUuid The Uuid of the target Json to redirect to.
     * @return void
     */
    public function setTargetUuid(UuidInterface $targetUuid): void
    {
        $this->targetUuid = $targetUuid;
    }

    /**
     * Returns the numerical datatype as a bitfield. Available types include: any, integer, double, boolean, string, array, object, null, number (integer/double), scalar (integer/double/boolean/string).
     *
     * @return integer
     */
    public function getType(): int
    {
        // If the target didn't exist yet, get it.

        $this->loadTarget();

        // Return the target's type.

        return $this->getTarget()->getType();
    }

    /**
     * Returns the name of the datatype of this class. Used for display and errors.
     *
     * @return string
     */
    public function getTypeName(): string
    {
        // If the target didn't exist yet, get it.

        $this->loadTarget();

        // Return the target's type name.

        return $this->getTarget()->getTypeName();
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
        return '** REDIRECT **';
    }

    /**
     * Sets the target based on the stored Uuid.
     *
     * Note that loading the target is deferred. If it wasn't,
     * then targeting by Uuid would require the structure to be
     * restricted to a specific order, which may not be possible.
     *
     * @return void
     */
    protected function loadTarget(): void
    {
        if ($this->getTarget() === null) {

            // Set the target if the target wasn't already set.

            $this->setTarget(self::getJsonByUuid($this->getTargetUuid()));
        }
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
        // If the target didn't exist yet, get it.

        $this->loadTarget();

        // Return whether or not the input matches the target structure.

        return $this->getTarget()->compareStructure($input, $reports, $statistics);
    }
}
