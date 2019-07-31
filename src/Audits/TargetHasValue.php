<?php namespace Celestriode\JsonConstructure\Audits;

use Celestriode\Constructure\Audits\AbstractAudit;
use Celestriode\JsonConstructure\Utils\JsonPath;
use Celestriode\Constructure\Statistics\Statistics;
use Celestriode\Constructure\Reports\ReportsInterface;
use Celestriode\Constructure\StructureInterface;
use Celestriode\Constructure\InputInterface;
use Celestriode\JsonConstructure\Structures\AbstractScalar;
use Celestriode\JsonConstructure\Exceptions\PathException;
use Celestriode\Constructure\Exceptions\AuditFailed;
use Celestriode\Constructure\Reports\Message;
use Celestriode\Constructure\Predicates\PredicateInterface;
use Celestriode\JsonConstructure\Predicates\AbstractJsonPredicate;

/**
 * Tests if the element at the provided path contains any of the specified values.
 *
 * The target element must extend the AbstractScalar class.
 *
 * You can simply use "@" to target the current element.
 */
class TargetHasValue extends AbstractAudit
{
    /** @var JsonPath $targetPath The parsed path to the target element. */
    private $targetPath;
    /** @var array $acceptedValues The values that can be accepted. */
    private $acceptedValues = [];

    public function __construct(JsonPath $targetPath, ...$acceptedValues)
    {
        $this->targetPath = $targetPath;
        $this->acceptedValues = $acceptedValues;
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

        $predicate = static::getAsPredicate($this->targetPath, ...$this->acceptedValues);

        // Test the predicate. If it fails, add any issues deriving from it to reports.

        if (!$predicate->test($input)) {
            foreach ($predicate->getIssues() as $issue) {
                $reports->addReport($issue->getReportMessage());
            }

            return false;
        }

        // Otherwise the predicate passed, return true.

        return true;
    }

    /**
     * Returns the audit as though it were a predicate.
     *
     * @param JsonPath $targetPath The path to the target Json.
     * @param mixed ...$values The values accepted in Json.
     * @return PredicateInterface
     */
    public static function getAsPredicate(JsonPath $targetPath, ...$values): PredicateInterface
    {
        return new class($targetPath, ...$values) extends AbstractJsonPredicate {
            /** @var JsonPath $targetPath The parsed path to the target element. */
            private $targetPath;
            /** @var array $acceptedValues The values that can be accepted. */
            private $acceptedValues = [];

            public function __construct(JsonPath $targetPath, ...$values)
            {
                $this->targetPath = $targetPath;
                $this->acceptedValues = $values;
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

                    // If it's the wrong datatype, return false.

                    if (!($target instanceof AbstractScalar)) {
                        $this->addIssue(AuditFailed::create(Message::error($input->getContext(), 'Target must be of type "scalar", was instead type %s', $input->getTypeName())));

                        return false;
                    }

                    // Otherwise return whether or not the value exists within the array of accepted values.
                    
                    if (!in_array($target->getValue(), $this->acceptedValues)) {
                        $this->addIssue(AuditFailed::create(Message::warn($target->getContext(), 'Invalid value %s, should be one of: %s', (string)$target->getValue(), implode(', ', $this->acceptedValues))));

                        return false;
                    }

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
