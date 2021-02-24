<?php

namespace GPM\Config\Parts\Element;

use GPM\Config\Parts\Part;
use GPM\Config\Rules;

/**
 * Class PluginEvent
 *
 * @property-read string $name
 * @property-read int $priority
 * @property-read string $propertySet
 *
 * @package GPM\Config\Parts\Element
 */
class PluginEvent extends Part
{

    protected $keyField = 'name';

    /** @var string  */
    protected $name = '';

    /** @var int  */
    protected $priority = 0;

    /** @var string */
    protected $propertySet = '';

    protected $rules = [
        'name' => [Rules::isString, Rules::notEmpty],
        'propertySet' => [Rules::isString],
    ];

}
