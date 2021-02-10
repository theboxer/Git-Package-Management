<?php
namespace GPM\Config\Parts;

use GPM\Config\Config;
use GPM\Config\Rules;
use MODX\Revolution\modPropertySet;
use Psr\Log\LoggerInterface;

/**
 * Class PropertySet
 *
 * @property-read string $name
 * @property-read string $description
 * @property-read string[] $category
 * @property-read Property[] $properties
 *
 * @package GPM\Config\Parts
 */
class PropertySet extends Part
{
    use HasCategory;
    use HasProperties;

    protected $keyField = 'name';

    /** @var string */
    protected $name = '';

    /** @var string */
    protected $description = '';

    protected $rules = [
        'name' => [Rules::isString, Rules::notEmpty ],
        'category' => [Rules::isArray, Rules::categoryExists],
        'properties' => [
            ['rule' => Rules::isArray, 'params' => ['itemRules' => [Rules::configPart]]]
        ]
    ];

    protected function prepareObject(int $category = null, bool $update = false, array $previousProperties = null)
    {
        /** @var \MODX\Revolution\modPropertySet $obj */
        $obj = null;

        if ($update) {
            $obj = $this->config->modx->getObject(modPropertySet::class, ['name' => $this->name]);
        }

        if ($obj === null) {
            $obj = $this->config->modx->newObject(modPropertySet::class);
            $obj->set('name', $this->name);
        }

        $obj->set('description', $this->description);

        if ($category !== null) {
            $obj->set('category', $category);
        }

        $newProperties = $this->getProperties();
        if (($update === true) && ($previousProperties !== null)) {
            $oldProperties = [];
            $currentProperties = [];

            foreach ($previousProperties as $previousProperty) {
                $oldProperties[$previousProperty['name']] = $previousProperty['value'];
            }

            /** @var modPropertySet $propertySet */
            $propertySet = $this->config->modx->getObject(modPropertySet::class, ['name' => $this->name]);
            if ($propertySet) {
                $currentProperties = $propertySet->get('properties');
            }

            foreach ($newProperties as &$newProperty) {
                if (isset($oldProperties[$newProperty['name']])) {
                    if ($oldProperties[$newProperty['name']] === $newProperty['value']) {
                        if (isset($currentProperties[$newProperty['name']])) {
                            $newProperty['value'] = $currentProperties[$newProperty['name']]['value'];
                        }
                    }
                }
            }
        }

        $obj->set('properties', $newProperties);

        return $obj;
    }

    public function setConfig(Config $config): void
    {
        parent::setConfig($config);

        foreach ($this->properties as $property) {
            $property->setConfig($config);
        }
    }

    public function getObject(int $category, array $previousProperties = null): modPropertySet
    {
        return $this->prepareObject($category, true, $previousProperties);
    }

    public function getBuildObject(): modPropertySet
    {
        return $this->prepareObject(null, false);
    }
}
