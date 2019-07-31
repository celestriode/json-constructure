<?php namespace Celestriode\JsonConstructure\Utils;

use Celestriode\Constructure\Reports\PrettifySupplierInterface;

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
}