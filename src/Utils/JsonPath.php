<?php namespace Celestriode\JsonConstructure\Utils;

use Celestriode\JsonConstructure\Structures\AbstractJson;
use Celestriode\JsonConstructure\Structures\JsonObject;
use Celestriode\JsonConstructure\Exceptions\PathException;
use Celestriode\Constructure\Reports\Message;
use Celestriode\JsonConstructure\Structures\Root;
use Celestriode\Constructure\Utils\MessageUtils;

/**
 * Utilities for traversing AbstractJson objects by way of string-based path syntax.
 *
 * Paths must start with either $ or @.
 *
 * $ = start the search from the root parent.
 * @ = start the search from the current depth.
 *
 * From there, you may ascend with the token ^. This will go up one parent, if possible.
 * Ascension tokens may be chained to continue going up. For example, the following will
 * start the search at the current depth and ascend 2 parents. The result of the path is
 * that parent.
 *
 * @^^
 *
 * Once you have ascended as high as you need to go, you can use the . token to descend to
 * specific children, which are specified as a key name after the token. For example, the
 * following will start at the root parent and return the child field "hello".
 *
 * $.test.hello
 *
 * That would match JSON such as: {"test": {"hello": true}}
 *
 * One final example, which ascends twice from the depth of the supplied AbstractJson, then
 * descends down the "find" object and ends at "me", whatever datatype it might be.
 *
 * @^^.find.me
 *
 * This does not support array traversal because it can only return a single AbstractJson
 * object. It may eventually support traversing specific indices within an array, or
 * performing a search within an array and returning only the first result. The second
 * sounds not particularly useful. Perhaps it should return a JsonCollection instead.
 * Maybe someday it will.
 */
class JsonPath
{
    /** @var string $rawPath The raw path before parsing. */
    private $rawPath;
    /** @var array $path The resulting path, parsed from string. */
    private $path;
    /** @var array $paths Every path that was parsed to prevent need from parsing the same path multiple time. */
    private static $cachedPaths = [];

    public function __construct(string $path)
    {
        $this->rawPath = $path;
        $this->path = $this->parsePath($path);
    }

    /**
     * Returns the raw path before being parsed.
     *
     * @return string
     */
    public function getRawPath(): string
    {
        return $this->rawPath;
    }

    /**
     * Returns the parsed path.
     *
     * @return array
     */
    public function getPath(): array
    {
        return $this->path;
    }

    /**
     * Returns all the cached paths as cached via self::from().
     *
     * @return array
     */
    public static function getCachedPaths(): array
    {
        return self::$cachedPaths;
    }

    /**
     * Parses the path string and returns the traversable path as a flat array.
     *
     * @param string $pathString The raw path to parse.
     * @return array
     */
    protected function parsePath(string $pathString): array
    {
        // If strlen is 0, throw

        if (strlen($pathString) == 0) {
            throw new \LogicException('Path cannot be empty');
        }

        $path = [];

        $stringParts = preg_split('//u', $pathString, -1, PREG_SPLIT_NO_EMPTY);
        $findingKey = false;
        $buffer = '';

        // If the first character isn't current or root, throw error.

        if ($stringParts[0] != '@' && $stringParts[0] != '$') {
            throw new \LogicException('Path must begin with current (@) or root ($)');
        }

        // Cycle through each character

        for ($i = 0, $j = count($stringParts); $i < $j; $i++) {
            $currentChar = $stringParts[$i];
            $currentPathIndex = count($path);

            // If a key is being found...

            if ($findingKey) {

                // Add current character to buffer as long as it's not a backslash and the character before it wasn't a backslash. This is so shitty.

                if ($currentChar != '\\' || ($i > 0 && $stringParts[$i - 1] == '\\')) {
                    $buffer .= $currentChar;
                }

                // If the next character is an unescaped control character, we're done with this child.

                if (($i + 1 != $j && in_array($stringParts[$i + 1], ['$', '^', '.']) && $currentChar !== '\\') || $i + 1 == $j) {
                    $path[$currentPathIndex - 1]['key'] = $buffer;
                    $buffer = '';
                    $findingKey = false;
                }
            } else {

                // Type: @ (current)

                if ($currentChar == '@') {
                    if ($i != 0) {
                        throw new \LogicException('Cannot use current if not at beginning');
                    }

                    $path[] = [
                        'type' => 'current'
                    ];
                } elseif ($currentChar == '$') {

                    // Type: $ (root)

                    if ($i != 0) {
                        throw new \LogicException('Cannot go to root if not at beginning');
                    }

                    $path[] = [
                        'type' => 'root'
                    ];
                } elseif ($currentChar == '^') {

                    // Type: ^ (ascend)

                    if (isset($path[$currentPathIndex - 1]) && $path[$currentPathIndex - 1]['type'] == 'child') {
                        throw new \LogicException('Cannot ascend after going to a child');
                    }

                    if (isset($path[$currentPathIndex - 1]) && $path[$currentPathIndex - 1]['type'] == 'root') {
                        throw new \LogicException('Cannot ascend after going to the root');
                    }

                    $path[] = [
                        'type' => 'ascend'
                    ];
                } elseif ($currentChar == '.') {

                    // Type: . (child)

                    $path[] = [
                        'type' => 'child',
                        'key' => ''
                    ];

                    $findingKey = true;
                } else {

                    // Invalid type, whatever it may be.

                    throw new \LogicException('Unexpected token "' . $currentChar . '"');
                }
            }
        }

        // Return the completed path.

        return $path;
    }

    /**
     * Passes the provided Json through the path. If the path successfully
     * finds the Json it needs to, it will be returned. Otherwise, errors galore.
     *
     * @param AbstractJson $json The Json to use the path on to find specific data.
     * @return AbstractJson
     */
    public function findInJson(AbstractJson $json): AbstractJson
    {
        $currentJson = ($json instanceof Root) ? $json->getRootType() : $json;

        foreach ($this->path as $path) {

            // Type: root

            if ($path['type'] == 'root') {
                $root = $json;

                while ($root->getParentInput() !== null) {
                    $root = $root->getParentInput();
                }

                if ($root instanceof Root) {
                    $currentJson = $root->getRootType();
                } else {
                    $currentJson = $root;
                }
            }

            // Type: ascend.

            if ($path['type'] == 'ascend') {
                if ($currentJson->getParentInput() === null || $currentJson->getParentInput() instanceof Root) {
                    throw PathException::create(Message::error($json, 'Path traversal failed with path %s: could not ascend far enough due to lack of parent', MessageUtils::value($this->rawPath)));
                }

                $currentJson = $currentJson->getParentInput();
            }

            // Type: child

            if ($path['type'] == 'child') {
                if (!($currentJson instanceof JsonObject)) {
                    throw PathException::create(Message::error($json, 'Path traversal failed with path %s: target for traversal is not an object', MessageUtils::value($this->rawPath)));
                }

                if (!$currentJson->hasField($path['key'])) {
                    throw PathException::create(Message::error($json, 'Path traversal failed with path %s: could not find field with key %s', MessageUtils::value($this->rawPath), MessageUtils::key($path['key'])));
                }

                $currentJson = $currentJson->getField($path['key'])->getJson();
            }
        }

        // Return the Json that was found.

        return $currentJson;
    }

    /**
     * Creates a new parsed path from the provided string, or returns the
     * parsed path if it was already parsed.
     *
     * @param string $path The path to parse.
     * @return self
     */
    public static function from(string $path): self
    {
        // If the path was already parsed, return it.

        if (array_key_exists($path, self::$cachedPaths)) {
            return self::$cachedPaths[$path];
        }

        // Otherwise parse, save, and return the path.

        return self::$cachedPaths[$path] = new static($path);
    }
}
