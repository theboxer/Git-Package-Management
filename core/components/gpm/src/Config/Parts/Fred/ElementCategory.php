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
 *
 * @package GPM\Config\Parts\Element
 */
class ElementCategory extends Part
{
    use Uuid;

    protected $keyField = 'name';

    /** @var string */
    protected $name = '';

    /** @var array */
    protected $templates = [];

    /** @var int */
    protected $rank = 0;

    protected $rules = [
        'name' => [Rules::isString, Rules::notEmpty],
        'rank' => [Rules::isInt],
        'templates' => [Rules::isArray],
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

        $obj = $this->config->modx->getObject('\\Fred\\Model\\FredElementCategory', $where);

        if ($obj === null) {
            $obj = $this->config->modx->newObject('\\Fred\\Model\\FredElementCategory');

            $obj->set('theme', $this->config->fred->getThemeId());
        } else {
            $this->config->modx->removeCollection('\\Fred\\Model\\FredElementCategoryTemplateAccess', ['category' => $obj->get('id')]);
        }

        if (!empty($this->uuid)) {
            $obj->set('uuid', $this->uuid);
        }

        $obj->set('name', $this->name);
        $obj->set('rank', $this->rank);

        $templates = [];
        foreach ($this->templates as $template) {
            $templateId = $this->config->fred->getTemplateId($template);
            if (empty($templateId)) continue;

            $templateObj = $this->config->modx->newObject('\\Fred\\Model\\FredElementCategoryTemplateAccess');
            $templateObj->set('template', $templateId);
            $templates[] = $templateObj;
        }

        $obj->addMany($templates, 'ElementCategoryTemplatesAccess');

        return $obj;
    }

    public function deleteObject(): bool {
        if (empty($this->uuid)) return false;

        $toDelete = $this->config->modx->getObject('\\Fred\\Model\\FredElementCategory', ['uuid' => $this->uuid, 'theme' => $this->config->fred->getThemeId()]);
        if ($toDelete) {
            return $toDelete->remove();
        }

        return false;
    }

    public function getBuildObject()
    {
        if (empty($this->uuid)) {
            throw new NoUuidException('element category: ' . $this->name);
        }

        $obj = $this->config->modx->getObject('\\Fred\\Model\\FredElementCategory', ['uuid' => $this->uuid]);

        $elements = $this->getBuildElements();
        $obj->addMany($elements, 'Elements');

        return $obj;
    }

    private function getBuildElements()
    {
        $elements = $this->config->fred->getElementsForCategory($this->name);

        $buildElementObjects = [];

        foreach ($elements as $el) {
            $buildElementObjects[] = $el->getBuildObject();
        }

        return $buildElementObjects;
    }
}
