<?php namespace Celestriode\JsonConstructure\Audits;

use Celestriode\Constructure\Audits\AbstractAudit;
use Celestriode\JsonConstructure\Utils\JsonPath;
use Celestriode\Constructure\Statistics\Statistics;
use Celestriode\Constructure\Reports\ReportsInterface;
use Celestriode\Constructure\StructureInterface;
use Celestriode\Constructure\InputInterface;
use Celestriode\JsonConstructure\Exceptions\PathException;
use Celestriode\JsonConstructure\Predicates\AbstractJsonPredicate;

/**
 * Tests if a target Json exists.
 */
class TargetExists extends AbstractAudit
{
    /** @var JsonPath $targetPath The parsed path to the target element. */
    private $targetPath;

    public function __construct(JsonPath $targetPath)
    {
        $this->targetPath = $targetPath;
    }

    /**
     * Performs extra tasks to validate integrity of input.
     *
     * Should throw Celestriode\Exceptions\AuditFailed if the audit could not be performed.
     * It should be thrown with the issue message itself rather than creating a report.
     * Use reports instead for "debug", "info", and "warn" severities. But really it's up
     * to you how to do things.
     *
     * @param InputInterface $input The input to audit.
     * @param StructureInterface $expected The expected structure if needed.
     * @param ReportsInterface $reports Reports to add to.
     * @param Statistics $statistics Statistics to manipulate.
     * @return bool
     */
    public function audit(InputInterface $input, StructureInterface $expected, ReportsInterface $reports, Statistics $statistics): bool
    {
        // Create the partner predicate for this audit.

        $predicate = static::getAsPredicate($this->targetPath);

        // Test the predicate. If it fails, add any issues deriving from it to reports.

        if (!$predicate->test($input)) {
            $this->addIssuesToReports($input, $reports, ...$predicate->getIssues());

            return false;
        }

        // Otherwise the predicate passed, return true.

        return true;
    }

    /**
     * Returns the audit as though it were a predicate.
     *
     * @param JsonPath $targetPath The path to the target Json.
     * @return AbstractJsonPredicate
     */
    public static function getAsPredicate(JsonPath $targetPath): AbstractJsonPredicate
    {
        return new class($targetPath) extends AbstractJsonPredicate {
            /** @var JsonPath $targetPath The parsed path to the target element. */
            private $targetPath;

            public function __construct(JsonPath $targetPath)
            {
                $this->targetPath = $targetPath;
            }

            /**
             * Performs a test against the input. Predicates are used when you need to silently test
             * a condition against the input, unlike audits which are very loud.
             *
             * @param InputInterface $input The input to test.
             * @return boolean
             */
            public function test(InputInterface $input): bool
            {
                // Skip if the structure is not a Json class.

                if (!$this->isCorrectStructure($input)) {
                    return false;
                }

                // Get the target from the path.

                try {
                    $target = $this->targetPath->findInJson($input);

                    // If there weren't any errors, return true.

                    return true;
                } catch (PathException $e) {

                    // There was some path error, return false.

                    $this->addIssue($e);

                    return false;
                }
            }
        };
    }
}
