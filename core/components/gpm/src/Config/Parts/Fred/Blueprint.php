<?php
namespace GPM\Config\Parts\Fred;

use GPM\Config\Config;
use GPM\Config\FileParser;
use GPM\Config\Parts\Part;
use GPM\Config\Rules;

/**
 * Class Category
 *
 * @property-read string $name
 * @property-read string $category
 * @property-read string $description
 * @property-read string $image
 * @property-read int $rank
 * @property-read string $file
 * @property-read string $absoluteFilePath
 * @property-read array $templates
 * @property-read array $public
 *
 * @package GPM\Config\Parts\Element
 */
class Blueprint extends Part
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

    /** @var string */
    protected $absoluteFilePath = '';

    /** @var int */
    protected $rank;

    /** @var bool */
    protected $public = true;

    protected $rules = [
        'uuid' => [Rules::isString, Rules::notEmpty],
        'name' => [Rules::isString, Rules::notEmpty],
        'category' => [Rules::isString, Rules::notEmpty],
        'description' => [Rules::isString],
        'image' => [Rules::isString, Rules::notEmpty],
        'file' => [Rules::isString, Rules::notEmpty, Rules::elementFileExists],
        'templates' => [Rules::isArray],
        'public' => [Rules::isBool],
    ];

    protected function generator(): void
    {
        if (empty($this->image)) {
            $this->image = "https://via.placeholder.com/300x150?text=$this->name";
        }

        if (!empty($this->name) && empty($this->file)) {
            $this->file = $this->name . '.json';
        }

        $baseElementsPath = $this->config->paths->build . 'fred' . DIRECTORY_SEPARATOR . 'blueprints' . DIRECTORY_SEPARATOR;
        $this->absoluteFilePath = $baseElementsPath . $this->uuid . '.json';
    }

    public function setConfig(Config $config): void
    {
        parent::setConfig($config);
    }

    public function getObject()
    {
        $obj = $this->config->modx->getObject('\\Fred\\Model\\FredBlueprint', ['uuid' => $this->uuid]);

        if ($obj === null) {
            $obj = $this->config->modx->newObject('\\Fred\\Model\\FredBlueprint');
            $obj->set('name', $this->name);
        } else {
            $this->config->modx->removeCollection('\\Fred\\Model\\FredBlueprintTemplateAccess', ['blueprint' => $obj->get('id')]);
        }

        $obj->set('uuid', $this->uuid);
        $obj->set('category', $this->config->fred->getBlueprintCategoryId($this->category));
        $obj->set('image', $this->image);
        $obj->set('description', $this->description);

        if ($this->rank !== null) {
            $obj->set('rank', $this->rank);
        }

        $obj->set('content', file_get_contents($this->absoluteFilePath));

        $templates = [];
        foreach ($this->templates as $template) {
            $templateId = $this->config->fred->getTemplateId($template);
            if (empty($templateId)) continue;

            $templateObj = $this->config->modx->newObject('\\Fred\\Model\\FredBlueprintTemplateAccess');
            $templateObj->set('template', $templateId);
            $templates[] = $templateObj;
        }

        $obj->addMany($templates, 'BlueprintTemplatesAccess');

        return $obj;
    }

    public function deleteObject(): bool {
        $toDelete = $this->config->modx->getObject('\\Fred\\Model\\FredBlueprint', ['uuid' => $this->uuid]);
        if ($toDelete) {
            return $toDelete->remove();
        }

        return false;
    }

    public function getBuildObject()
    {
        if (empty($this->uuid)) {
            throw new NoUuidException('blueprint: ' . $this->category . '/' . $this->name);
        }

        $obj = $this->config->modx->getObject('\\Fred\\Model\\FredBlueprint', ['uuid' => $this->uuid]);
        $obj->set('createdBy', 0);

        return $obj;
    }
}
