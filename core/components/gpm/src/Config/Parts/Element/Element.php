<?php
namespace GPM\Config\Parts\Element;

use GPM\Config\Config;
use GPM\Config\Parts\Part;
use GPM\Config\Parts\Property;
use Psr\Log\LoggerInterface;

/**
 * Class Element
 *
 * @property-read string $name
 * @property-read string $description
 * @property-read string $file
 * @property-read string[] $category
 * @property-read int $propertyPreProcess
 * @property-read Property[] $properties
 * @property-read string $absoluteFilePath
 * @property-read string $filePath
 * @property-read string[] $propertySets
 *
 * @package GPM\Config\Parts\Element
 */
abstract class Element extends Part
{
    /** @var string */
    protected $name = '';

    /** @var string */
    protected $description = '';

    /** @var string */
    protected $file = '';

    /** @var string[] */
    protected $category = [];

    /** @var int $propertyPreProcess */
    protected $propertyPreProcess = 0;

    /** @var Property[] */
    protected $properties = [];

    /** @var string */
    protected $absoluteFilePath = '';

    /** @var string */
    protected $filePath = '';

    /** @var string */
    protected $type = '';

    /** @var string */
    protected $extension = '';

    /** @var string[] */
    protected $propertySets = [];

    protected function generator(): void
    {
        parent::generator();

        if (!empty($this->name) && empty($this->file)) {
            $this->file = $this->name . '.' . $this->extension;
        }

        $baseSnippetsPath = $this->config->paths->core . 'elements' . DIRECTORY_SEPARATOR . $this->type . 's' . DIRECTORY_SEPARATOR;

        $pathsToCheck = [];
        $pathsToCheck[] = $baseSnippetsPath . $this->file;

        if (!empty($this->category)) {
            $category = implode(DIRECTORY_SEPARATOR, $this->category) . DIRECTORY_SEPARATOR;
            $pathsToCheck[] = $baseSnippetsPath . $category . $this->file;
            $pathsToCheck[] = $baseSnippetsPath . str_replace(' ', '-', strtolower($category)) . $this->file;
        }

        foreach ($pathsToCheck as $path) {
            if (file_exists($path)) {
                $this->absoluteFilePath = $path;
                $this->filePath = str_replace($this->config->paths->core, '', $this->absoluteFilePath);
                break;
            }
        }
    }

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

    public function setConfig(Config $config): void
    {
        parent::setConfig($config);

        foreach ($this->properties as $property) {
            $property->setConfig($config);
        }
    }

    public static function getSkipProperties(): array
    {
        return array_merge(parent::getSkipProperties(), ['filePath', 'absoluteFilePath', 'extension', 'type']);
    }

    public static function getPrivateProperties(): array
    {
        return array_merge(parent::getPrivateProperties(), ['extension', 'type']);
    }

    protected function prepareObject(int $category = null, bool $update = false, bool $static = true, bool $debug = false)
    {
        $class = '\\MODX\\Revolution\\mod' . ucfirst($this->type);

        /** @var \MODX\Revolution\modSnippet|\MODX\Revolution\modChunk|\MODX\Revolution\modTemplate|\MODX\Revolution\modPlugin $obj */
        $obj = null;

        $pk = 'name';
        if ($this->type === 'template') {
            $pk = 'templatename';
        }

        if ($update) {
            $obj = $this->config->modx->getObject($class, [$pk => $this->name]);
        }

        if ($obj === null) {
            $obj = $this->config->modx->newObject($class);
            $obj->set('name', $this->name);
        }

        $obj->set('description', $this->description);

        if ($category !== null) {
            $obj->set('category', $category);
        }

        $obj->set('property_preprocess', $this->propertyPreProcess);

        if ($static) {
            if ($debug) {
                $obj->set('content', 'return include("' . $this->absoluteFilePath . '");');
                $obj->set('static', 0);
                $obj->set('static_file', '');
            } else {
                $obj->set('content', '');
                $obj->set('static', 1);
                $obj->set('static_file', '[[++' . $this->config->general->lowCaseName . '.core_path]]' . $this->filePath);
            }
        } else {
            $obj->set('content', file_get_contents($this->absoluteFilePath));
            $obj->set('static', 0);
            $obj->set('static_file', '');
        }

        $obj->setProperties($this->getProperties());

        return $obj;
    }

    public function validate(LoggerInterface $logger): bool
    {
        $valid = true;

        if (empty($this->name)) {
            $logger->error(ucfirst($this->type) . " - name is required");
            $valid = false;
        }

        if (empty($this->absoluteFilePath) || !file_exists($this->absoluteFilePath)) {
            $logger->error(ucfirst($this->type) . ": {$this->file} - file doesn't exist");
            $valid = false;
        }

        if (!is_int($this->propertyPreProcess)) {
            $logger->error(ucfirst($this->type) . ": {$this->name} - propertyPreProcess has to be integer");
            $valid = false;
        }

        if (!empty($this->category)) {
            $configCategories = $this->config->categories;
            foreach ($this->category as $category) {
                $found = false;
                foreach ($configCategories as $configCategory) {
                    if ($configCategory->name === $category) {
                        $configCategories = $configCategory->children;
                        $found = true;
                        break;
                    }
                }

                if ($found === false) {
                    $logger->error(ucfirst($this->type) . ": {$this->name} - " . implode('/', $this->category) . " category doesn't exist");
                    $valid = false;
                    break;
                }
            }
        }

        if ($valid) {
            $logger->debug(' - ' . ucfirst($this->type) . ': ' . $this->name);
        }

        foreach ($this->properties as $property) {
            $valid = $property->validate($logger, $this->name) && $valid;
        }

        return $valid;
    }

    abstract public function getObject(int $category, bool $debug = false);

    abstract public function getBuildObject();
}
