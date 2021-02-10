<?php
namespace GPM\Config\Parts\Element;

use GPM\Config\Config;
use GPM\Config\Parts\Part;
use GPM\Config\Rules;
use Psr\Log\LoggerInterface;

/**
 * Class Category
 *
 * @property-read string $name
 * @property-read integer $rank
 * @property-read Category[] $children
 *
 * @package GPM\Config\Parts\Element
 */
class Category extends Part
{
    protected $keyField = 'name';

    /** @var string */
    protected $name = '';

    /** @var integer */
    protected $rank = 0;

    /** @var Category[] */
    protected $children = [];

    protected $rules = [
        'name' => [Rules::isString, Rules::notEmpty],
        'children' => [
            ['rule' => Rules::isArray, 'params' => ['itemRules' => [Rules::configPart]]]
        ]
    ];

    protected function generator(): void
    {
    }

    protected function setChildren(array $children): void
    {
        foreach ($children as $category) {
            $this->children[] = new Category($category, $this->config);
        }
    }

    public function setConfig(Config $config): void
    {
        parent::setConfig($config);

        foreach ($this->children as $category) {
            $category->setConfig($config);
        }
    }
}
