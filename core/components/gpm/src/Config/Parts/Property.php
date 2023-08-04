<?php
namespace GPM\Config\Parts;

use GPM\Config\Rules;
use GPM\Utils\Types;
use Psr\Log\LoggerInterface;

/**
 * Class Property
 *
 * @property-read string $name
 * @property-read string $description
 * @property-read string $type
 * @property-read string $value
 * @property-read string $lexicon
 * @property-read string $area
 *
 * @package GPM\Config\Parts
 */
class Property extends Part
{

    protected $keyField = 'name';

    /** @var string */
    protected $name = '';

    /** @var string */
    protected $description = '';

    /** @var string */
    protected $type = '';

    /** @var string */
    protected $value = '';

    /** @var string */
    protected $lexicon = '';

    /** @var string */
    protected $area = '';

    protected $options = [];

    protected $rules = [
        'name' => [Rules::isString, Rules::notEmpty],
        'type' => [Rules::isString],
        'options' => [
            ['rule' => Rules::isArray, 'params' => ['itemRules' => [Rules::isArray]]]
        ],
    ];

    protected function generator(): void
    {
        if (empty($this->type)) {
            $this->type = 'textfield';
        }

        if (empty($this->lexicon)) {
            $this->lexicon = $this->config->general->lowCaseName . ':properties';
        }
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'desc' => $this->description,
            'type' => $this->type,
            'value' => $this->value,
            'lexicon' => $this->lexicon,
            'area' => $this->area,
            'options' => $this->options,
        ];
    }
}
