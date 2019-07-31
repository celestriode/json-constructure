<?php namespace Celestriode\JsonConstructure\Utils;

use Celestriode\Constructure\Reports\PrettifySupplierInterface;
use Celestriode\JsonConstructure\Structures\JsonObject;
use Celestriode\JsonConstructure\Structures\JsonArray;

/**
 * A JSON-specific prettifier.
 */
class JsonPrettifier implements PrettifySupplierInterface
{
    /** @var bool $prettyPrint Whether or not to use JSON_PRETTY_PRINT. */
    private $prettyPrint;
    /** @var bool $unescapedSlashes Whether or not to use JSON_UNESCAPED_SLASHES. */
    private $unescapedSlashes;
    /** @var bool $unescapedUnicode Whether or not to use JSON_UNESCAPED_UNICODE. */
    private $unescapedUnicode;

    public function __construct(bool $prettyPrint = true, bool $unescapedSlashes = true, bool $unescapedUnicode = true)
    {
        $this->prettyPrint = $prettyPrint;
        $this->unescapedSlashes = $unescapedSlashes;
        $this->unescapedUnicode = $unescapedUnicode;
    }

    /**
     * Turns an object into a prettified string.
     *
     * @param \stdClass $rawObject The object to transform.
     * @param JsonObject $object A corresponding JsonObject, if applicable.
     * @return string
     */
    public function prettifyObject(\stdClass $rawObject, JsonObject $object = null): string
    {
        return json_encode($rawObject, ($this->prettyPrint ? JSON_PRETTY_PRINT : 0) | ($this->unescapedSlashes ? JSON_UNESCAPED_SLASHES : 0) | ($this->unescapedUnicode ? JSON_UNESCAPED_UNICODE : 0));
    }

    /**
     * Turns an array into a prettified string.
     *
     * @param array $rawArray The array to transform.
     * @param JsonArray $array A corresponding JsonArray, if applicable.
     * @return string
     */
    public function prettifyArray(array $rawArray, JsonArray $array = null): string
    {
        return json_encode($rawArray, ($this->prettyPrint ? JSON_PRETTY_PRINT : 0) | ($this->unescapedSlashes ? JSON_UNESCAPED_SLASHES : 0) | ($this->unescapedUnicode ? JSON_UNESCAPED_UNICODE : 0));
    }

    /**
     * Takes in an ugly string and transforms it through whatever means necessary to make it pretty.
     *
     * Returns the pretty string.
     *
     * @param string $string The string to prettify.
     * @return string
     */
    public function prettify(string $string): string
    {
        return json_encode($string, ($this->prettyPrint ? JSON_PRETTY_PRINT : 0) | ($this->unescapedSlashes ? JSON_UNESCAPED_SLASHES : 0) | ($this->unescapedUnicode ? JSON_UNESCAPED_UNICODE : 0));
    }

    /**
     * Prettifies a string that is designated as a value.
     *
     * @param string $value The string to prettify.
     * @return string
     */
    public function prettifyValue(string $value): string
    {
        return $this->prettify($value);
    }

    /**
     * Prettifies a string that is designated as a key.
     *
     * @param string $key The string to prettify.
     * @return string
     */
    public function prettifyKey(string $key): string
    {
        return $this->prettify($key);
    }

    /**
     * Prettifies a string using a supplied closure.
     *
     * @param string $string The string to prettify using the closure.
     * @param \Closure $func The closure to prettify the string with.
     * @return string
     */
    public function prettifyDynamic(string $string, \Closure $func): string
    {
        return (string)$func($this, $string);
    }
}
