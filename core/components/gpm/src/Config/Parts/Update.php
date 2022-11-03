<?php
namespace GPM\Config\Parts;

use GPM\Config\Rules;
use Psr\Log\LoggerInterface;

/**
 * Class Update
 *
 * @property-read array $scriptsBefore
 * @property-read array $scriptsAfter

 *
 * @package GPM\Config\Parts
 */
class Update extends Part
{

    /** @var string[] */
    protected $scriptsBefore = [];

    /** @var string[] */
    protected $scriptsAfter = [];

    protected $rules = [
        'scriptsBefore' => [
            ['rule' => Rules::isArray, 'params' => ['itemRules' => [Rules::isString, Rules::scriptExists]]]
        ],
    ];

    protected function generator(): void
    {

    }
}
