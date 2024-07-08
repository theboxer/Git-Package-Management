<?php
namespace GPM\Config\Parts\Fred;

use GPM\Config\Config;
use GPM\Config\Parts\Part;
use GPM\Config\Rules;

/**
 * Class Category
 *
 * @property-read string $name
 * @property-read int $rank
 * @property-read array $templates
 * @property-read bool $public
 *
 * @package GPM\Config\Parts\Element
 */
class BlueprintCategory extends Part
{
    use Uuid;

    protected $keyField = 'name';

    /** @var string */
    protected $name = '';

    /** @var array */
    protected $templates = [];

    /** @var bool */
    protected $public = true;

    /** @var int */
    protected $rank;

    protected $rules = [
        'name' => [Rules::isString, Rules::notEmpty],
        'templates' => [Rules::isArray],
        'public' => [Rules::isBool],
    ];

    protected function generator(): void
    {
    }

    public function setConfig(Config $config): void
    {
        parent::setConfig($config);
    }

    public function getObject()
    {
        $where = empty($this->uuid) ? ['name' => $this->name] : ['uuid' => $this->uuid];

        $obj = $this->config->modx->getObject('\\Fred\\Model\\FredBlueprintCategory', $where);

        if ($obj === null) {
            $obj = $this->config->modx->newObject('\\Fred\\Model\\FredBlueprintCategory');
            $obj->set('name', $this->name);
            $obj->set('theme', $this->config->fred->getThemeId());
        } else {
            $this->config->modx->removeCollection('\\Fred\\Model\\FredBlueprintCategoryTemplateAccess', ['category' => $obj->get('id')]);
        }

        if (!empty($this->uuid)) {
            $obj->set('uuid', $this->uuid);
        }

        $obj->set('rank', $this->rank);
        $obj->set('public', $this->public);

        $templates = [];
        foreach ($this->templates as $template) {
            $templateId = $this->config->fred->getTemplateId($template);
            if (empty($templateId)) continue;

            $templateObj = $this->config->modx->newObject('\\Fred\\Model\\FredBlueprintCategoryTemplateAccess');
            $templateObj->set('template', $templateId);
            $templates[] = $templateObj;
        }

        $obj->addMany($templates, 'BlueprintCategoryTemplatesAccess');

        return $obj;
    }

    public function deleteObject(): bool {
        $toDelete = $this->config->modx->getObject('\\Fred\\Model\\FredBlueprintCategory', ['name' => $this->name, 'theme' => $this->config->fred->getThemeId()]);
        if ($toDelete) {
            return $toDelete->remove();
        }

        return false;
    }

    public function getBuildObject()
    {
        if (empty($this->uuid)) {
            throw new NoUuidException('blueprint category: ' . $this->name);
        }

        $obj = $this->config->modx->getObject('\\Fred\\Model\\FredBlueprintCategory', ['uuid' => $this->uuid]);
        $obj->set('createdBy', 0);

        $blueprints = $this->getBuildBlueprints();
        $obj->addMany($blueprints, 'Blueprints');

        return $obj;
    }

    private function getBuildBlueprints()
    {
        $blueprints = $this->config->fred->getBlueprintsForCategory($this->name);

        $buildBlueprintObjects = [];

        foreach ($blueprints as $bp) {
            if ($bp->public === false) continue;
            $buildBlueprintObjects[] = $bp->getBuildObject();
        }

        return $buildBlueprintObjects;
    }
}
