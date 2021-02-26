<?php
namespace GPM\Config\Parts\Element;

use GPM\Config\Config;
use GPM\Config\Parts\HasCategory;
use GPM\Config\Parts\HasProperties;
use GPM\Config\Parts\Part;
use GPM\Config\Rules;

/**
 * Class Element
 *
 * @property-read string $name
 * @property-read string $description
 * @property-read string $file
 * @property-read string[] $category
 * @property-read int $propertyPreProcess
 * @property-read string $absoluteFilePath
 * @property-read string $filePath
 * @property-read string[] $propertySets
 *
 * @package GPM\Config\Parts\Element
 */
abstract class Element extends Part
{
    use HasCategory;
    use HasProperties;

    protected $keyField = 'name';

    /** @var string */
    protected $name = '';

    /** @var string */
    protected $description = '';

    /** @var string */
    protected $file = '';

    /** @var int $propertyPreProcess */
    protected $propertyPreProcess = 0;

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

    protected $rules = [
        'name' => [Rules::isString, Rules::notEmpty],
        'category' => [Rules::isArray, Rules::categoryExists],
        'file' => [Rules::isString, Rules::notEmpty, Rules::elementFileExists],
        'properties' => [
            ['rule' => Rules::isArray, 'params' => ['itemRules' => [Rules::configPart]]]
        ],
        'propertySets' => [
            ['rule' => Rules::isArray, 'params' => ['itemRules' => [Rules::isString, Rules::propertySetExists]]]
        ]
    ];

    protected function generator(): void
    {
        parent::generator();

        if (!empty($this->name) && empty($this->file)) {
            $this->file = $this->name . '.' . $this->extension;
        }

        $baseSnippetsPath = $this->config->paths->core . 'elements' . DIRECTORY_SEPARATOR . $this->type . 's' . DIRECTORY_SEPARATOR;

        $pathsToCheck = [];
        $pathsToCheck[] = $baseSnippetsPath . $this->file;

        $this->absoluteFilePath = $baseSnippetsPath . $this->file;
        $this->filePath = str_replace($this->config->paths->core, '', $this->absoluteFilePath);

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

    abstract public function getObject(int $category, bool $debug = false);

    abstract public function getBuildObject();
}
