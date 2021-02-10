<?php
namespace GPM\Config\Parts;

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

    protected $keyField = 'name';

    /** @var string */
    protected $name = '';

    /** @var string */
    protected $description = '';

    /** @var string[] */
    protected $category = [];

    /** @var Property[] */
    protected $properties = [];

    protected $rules = [
        'name' => [Rules::isString, Rules::notEmpty ],
        'category' => [Rules::isArray, Rules::categoryExists],
        'properties' => [
            ['rule' => Rules::isArray, 'params' => ['itemRules' => [Rules::configPart]]]
        ]
    ];

    protected function setCategory(array $category): void
    {
        if (count($category) !== 1) {
            $this->category = $category;
            return;
        }

        $categoryPath = $this->findCategoryPath([], $category[0], $this->config->categories);

        if ($categoryPath[count($categoryPath) - 1] === $category[0]) {
            $this->category = $categoryPath;
        } else {
            $this->category = $category;
        }
    }

    /**
     * @param string[] $path
     * @param string $categoryName
     * @param \GPM\Config\Parts\Element\Category[] $categories
     */
    private function findCategoryPath(array $path, string $categoryName, array $categories): array
    {
        $futureScan = [];

        foreach ($categories as $category) {
            if ($category->name === $categoryName) {
                $path[] = $category->name;
                return $path;
            }

            if (!empty($category->children)) {
                $futureScan[] = ['name' => $category->name, 'children' => $category->children];
            }
        }

        foreach ($futureScan as $childCategories) {
            $found = $this->findCategoryPath(array_merge($path, [$childCategories['name']]), $categoryName, $childCategories['children']);
            if (!empty($found)) return $found;
        }

        return [];
    }

    protected function setProperties(array $properties): void
    {
        foreach ($properties as $property) {
            $this->properties[] = new Property($property, $this->config);
        }
    }

    public function getProperties(): array
    {
        $properties = [];

        foreach ($this->properties as $property) {
            $properties[] = $property->toArray();
        }

        return $properties;
    }

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

    public function getObject(int $category, array $previousProperties = null): modPropertySet
    {
        return $this->prepareObject($category, true, $previousProperties);
    }

    public function getBuildObject(): modPropertySet
    {
        return $this->prepareObject(null, false);
    }
}
