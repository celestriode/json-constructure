<?php namespace Celestriode\JsonConstructure;

use Celestriode\Constructure\Constructure;
use Celestriode\Constructure\Results;
use Celestriode\Constructure\Reports\ReportsInterface;
use Celestriode\Constructure\Statistics\Statistics;
use Celestriode\JsonConstructure\Structures\AbstractJson;
use Celestriode\JsonConstructure\Utils\Json;

/**
 * Compares an input Json with an expected structure.
 */
class JsonConstructure extends Constructure
{
    /**
     * Takes in a JSON string that will be turned into a structure
     * and compared with the expected Json structure.
     *
     * @param string $json The JSON string to validate.
     * @param AbstractJson $expected The expected Json structure that the input should match.
     * @param ReportsInterface $reports Reports to add to.
     * @param Statistics $statistics Statistics to manipulate.
     * @return Results
     */
    public static function validateFromString(string $json, AbstractJson $expected, ReportsInterface $reports = null, Statistics $statistics = null): Results
    {
        return parent::validate(Json::stringToStructure($json), $expected, $reports, $statistics);
    }
}
