<?php
namespace GPM\Config\Parts;

use GPM\Config\Rules;
use Psr\Log\LoggerInterface;

/**
 * Class General
 *
 * @property-read string $name
 * @property-read string $lowCaseName
 * @property-read string $description
 * @property-read string $namespace
 * @property-read string $author
 * @property-read string $version
 *
 * @package GPM\Config\Parts
 */
class General extends Part
{
    /** @var string */
    protected $name = '';

    /** @var string */
    protected $lowCaseName = '';

    /** @var string */
    protected $description = '';

    /** @var string */
    protected $namespace = '';

    /** @var string */
    protected $author = '';

    /** @var string */
    protected $version = '';

    protected $rules = [
        'name' => [Rules::isString, Rules::notEmpty],
        'version' => [Rules::isString, Rules::notEmpty],
    ];

    protected function generator(): void
    {
        if (empty($this->lowCaseName)) {
            $this->lowCaseName = strtolower(str_replace(' ', '', $this->name));
        }

        if (empty($this->namespace)) {
            $this->namespace = ucfirst($this->lowCaseName);
        }
    }
}
