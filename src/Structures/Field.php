<?php namespace Celestriode\JsonConstructure\Structures;

use Celestriode\Constructure\Reports\PrettifySupplierInterface;
use Celestriode\JsonConstructure\Utils\JsonPrettifier;

/**
 * A key/value field used by JsonObject.
 *
 * This holds the key and the value of a field, but also
 * allows for some options relating to fields, such as
 * whether or not they are required or if the key itself
 * is a placeholder for any key supplied in the input.
 */
class Field
{
    /** @var string $key The key of the field. */
    private $key;
    /** @var AbstractJson $json The value of the field. */
    private $json;
    /** @var boolean $required Whether or not the field is required. */
    private $required;
    /** @var boolean $isPlaceholder Whether or not the key is a placeholder. */
    private $isPlaceholder = false;

    public function __construct(string $key, AbstractJson $json, bool $required)
    {
        $this->setKey($key);
        $this->setJson($json);
        $this->setRequired($required);
    }

    /**
     * Returns the key of the field.
     *
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Returns the value of the field.
     *
     * @return AbstractJson
     */
    public function getJson(): AbstractJson
    {
        return $this->json;
    }

    /**
     * Returns whether or not the field is required.
     *
     * @return boolean
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * Returns whether or not the field's key is a placeholder for any key.
     *
     * @return boolean
     */
    public function isPlaceholder(): bool
    {
        return $this->isPlaceholder;
    }

    /**
     * Sets the key of the field.
     *
     * @param string $key The key of the field.
     * @return void
     */
    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    /**
     * Sets the value of the field.
     *
     * @param AbstractJson $json The value of the field.
     * @return void
     */
    public function setJson(AbstractJson $json): void
    {
        // If there was already Json, remove its reference to this field.

        if ($this->json !== null) {
            $this->json->setContainingField();
        }

        // Contain the new Json.

        $this->json = $json;
        $json->setContainingField($this);
    }

    /**
     * Marks the field as being required.
     *
     * @param boolean $required Whether or not the field is required.
     * @return void
     */
    public function setRequired(bool $required): self
    {
        $this->required = $required;

        return $this;
    }

    /**
     * Marks the field's key as a placeholder. This means the input
     * can have any key at all, so long as it still matches the value.
     *
     * @param boolean $placeholder Whether or not the field's key is a placeholder.
     * @return void
     */
    public function setIsPlaceholder(bool $placeholder): self
    {
        $this->isPlaceholder = $placeholder;

        return $this;
    }

    /**
     * Turns the field into a string for context.
     *
     * @param PrettifySupplierInterface $prettifier The optional prettifier.
     * @return string
     */
    public function fieldToContextString(PrettifySupplierInterface $prettifier = null): string
    {
        // Get either the parent's raw input if existent, or just the contained Json's input.

        $json = ($this->getJson()->getParentInput() !== null) ? $this->getJson()->getParentInput()->getRawInput() : $this->getJson()->getRawInput();

        // Use the prettifier if supplied.

        if ($prettifier instanceof JsonPrettifier) {
            return $prettifier->prettifyObject($json);
        }

        // Otherwise just do basic JSON encoding.

        return (string)json_encode($json);
    }

    /**
     * Returns a new field with the specified key.
     *
     * @param string $key The key of the field.
     * @param AbstractJson $json The value of the field.
     * @param boolean $required Whether or not the field is required.
     * @return self
     */
    public static function key(string $key, AbstractJson $json, bool $required = true): self
    {
        return new static($key, $json, $required);
    }

    /**
     * Provides a field in which the key can be anything at all.
     *
     * @param AbstractJson $json The value of the field.
     * @param boolean $required Whether or not the field is required.
     * @return self
     */
    public static function placeholder(AbstractJson $json, bool $required = true): self
    {
        $field = static::key('<any key (' . $json->getTypeName() . ')>', $json, $required);

        $field->setIsPlaceholder(true);

        return $field;
    }
}
