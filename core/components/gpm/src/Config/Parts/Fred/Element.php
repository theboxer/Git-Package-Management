<?php
namespace GPM\Config\Parts\Fred;

use GPM\Config\Config;
use GPM\Config\FileParser;
use GPM\Config\InvalidFileException;
use GPM\Config\Parts\Part;
use GPM\Config\Rules;
use GPM\Config\UnsupportedFileException;

/**
 * Class Category
 *
 * @property-read string $name
 * @property-read string $category
 * @property-read string $description
 * @property-read string $image
 * @property-read int $rank
 * @property-read string $file
 * @property-read string | null $content
 * @property-read string $absoluteFilePath
 * @property-read string $option_set
 * @property-read array $options_override
 * @property-read array $templates
 *
 * @package GPM\Config\Parts\Element
 */
class Element extends Part
{
    use Uuid;

    protected $keyField = 'name';

    /** @var string */
    protected $name = '';

    /** @var string */
    protected $category = '';

    /** @var string */
    protected $description = '';

    /** @var array */
    protected $templates = [];

    /** @var string */
    protected $image = '';

    /** @var string */
    protected $file = '';

    /** @var string | string */
    protected $content = null;

    /** @var string */
    protected $absoluteFilePath = '';

    protected $option_set = null;

    protected $options_override = null;

    /** @var int */
    protected $rank;

    protected $rules = [
        'name' => [Rules::isString, Rules::notEmpty],
        'category' => [Rules::isString, Rules::notEmpty],
        'description' => [Rules::isString],
        'image' => [Rules::isString, Rules::notEmpty],
        'file' => [Rules::isString, Rules::notEmpty, Rules::elementFileExists],
        'templates' => [Rules::isArray],
    ];

    protected function generator(): void
    {
        if (empty($this->image)) {
            $this->image = "https://via.placeholder.com/300x150?text=$this->name";
        }

        if (!empty($this->name) && empty($this->file)) {
            $this->file = $this->name . '.html';
        }

        $baseElementsPath = $this->config->paths->core . 'elements' . DIRECTORY_SEPARATOR . 'fred' . DIRECTORY_SEPARATOR . 'elements' . DIRECTORY_SEPARATOR;

        $pathsToCheck = [];
        $pathsToCheck[] = $baseElementsPath;
        $pathsToCheck[] = $baseElementsPath . $this->category . DIRECTORY_SEPARATOR;
        $pathsToCheck[] = $baseElementsPath . strtolower($this->category) . DIRECTORY_SEPARATOR;
        $pathsToCheck[] = $baseElementsPath . str_replace(' ', '-', strtolower($this->category));

        $optionFiles = [
            $this->name . '.options.json',
            $this->name . '.options.yaml',
            $this->name . '.options.yml'
        ];

        foreach ($pathsToCheck as $path) {
            if (empty($this->absoluteFilePath) && file_exists($path . $this->file)) {
                $this->absoluteFilePath = $path . $this->file;
            }

            foreach ($optionFiles as $optionFile) {
                if (empty($this->options_override) && file_exists($path . $optionFile)) {
                    $this->options_override = $path . $optionFile;
                }
            }
        }

        if (is_string($this->options_override)) {
            if (file_exists($this->options_override)) {
                $this->options_override = FileParser::parseFile($this->options_override);
            } else {
                $this->options_override = [];
            }
        }
    }

    public function setConfig(Config $config): void
    {
        parent::setConfig($config);
    }

    protected function prepareObject()
    {
        $where = empty($this->uuid) ? ['name' => $this->name] : ['uuid' => $this->uuid];

        $obj = $this->config->modx->getObject('\\Fred\\Model\\FredElement', $where);

        if ($obj === null) {
            $obj = $this->config->modx->newObject('\\Fred\\Model\\FredElement');
            $obj->set('name', $this->name);
        } else {
            $this->config->modx->removeCollection('\\Fred\\Model\\FredElementTemplateAccess', ['element' => $obj->get('id')]);
        }

        if (!empty($this->uuid)) {
            $obj->set('uuid', $this->uuid);
        }

        $obj->set('category', $this->config->fred->getElementCategoryId($this->category));
        $obj->set('image', $this->image);
        $obj->set('description', $this->description);

        if ($this->rank !== null) {
            $obj->set('rank', $this->rank);
        }

        if ($this->content !== null) {
            $obj->set('content', $this->content);
        } else {
            $obj->set('content', file_get_contents($this->absoluteFilePath));
        }

        if (!empty($this->option_set)) {
            $obj->set('option_set', $this->config->fred->getOptionSetId($this->option_set));
        }

        $obj->set('options_override', $this->options_override);

        $templates = [];
        foreach ($this->templates as $template) {
            $templateId = $this->config->fred->getTemplateId($template);
            if (empty($templateId)) continue;

            $templateObj = $this->config->modx->newObject('\\Fred\\Model\\FredElementTemplateAccess');
            $templateObj->set('template', $templateId);
            $templates[] = $templateObj;
        }

        $obj->addMany($templates, 'ElementTemplatesAccess');

        return $obj;
    }

    public function deleteObject(): bool {
        $toDelete = $this->config->modx->getObject('\\Fred\\Model\\FredElement', ['name' => $this->name]);
        if ($toDelete) {
            return $toDelete->remove();
        }

        return false;
    }

    public function getObject()
    {
        return $this->prepareObject();
    }
}
