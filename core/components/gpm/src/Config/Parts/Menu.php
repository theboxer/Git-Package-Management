<?php
namespace GPM\Config\Parts;

use GPM\Config\Rules;
use MODX\Revolution\modMenu;
use Psr\Log\LoggerInterface;

/**
 * Class Menu
 *
 * @property-read string $text
 * @property-read string $description
 * @property-read string $action
 * @property-read string $parent
 * @property-read string $icon
 * @property-read int $menuIndex
 * @property-read string $params
 * @property-read string $handler
 * @property-read string $permission
 *
 * @package GPM\Config\Parts
 */
class Menu extends Part
{
    protected $keyField = 'text';

    /** @var string */
    protected $text = '';

    /** @var string */
    protected $description = '';

    /** @var string */
    protected $action = '';

    /** @var string */
    protected $parent = '';

    /** @var string */
    protected $icon = '';

    /** @var int */
    protected $menuIndex = '';

    /** @var string */
    protected $params = '';

    /** @var string */
    protected $handler = '';

    /** @var string */
    protected $permission = '';

    protected $rules = [
        'text' => [Rules::isString, Rules::notEmpty],
        'menuIndex' => [Rules::isInt]
    ];

    protected function generator(): void
    {
        if (empty($this->parent)) {
            $this->parent = 'components';
        }
    }

    protected function prepareObject(bool $update = false): modMenu
    {
        /** @var modMenu $obj */
        $obj = null;

        if ($update) {
            $obj = $this->config->modx->getObject(modMenu::class, ['text' => $this->text]);
        }

        if ($obj === null) {
            $obj = $this->config->modx->newObject(modMenu::class);
            $obj->set('text', $this->text);
        }

        $obj->set('parent', $this->parent);
        $obj->set('description', $this->description);
        $obj->set('icon', $this->icon);
        $obj->set('menuindex', $this->menuIndex);
        $obj->set('params', $this->params);
        $obj->set('handler', $this->handler);
        $obj->set('permission', $this->permission);
        $obj->set('action', $this->action);
        $obj->set('namespace', $this->config->general->lowCaseName);

        return $obj;
    }

    public function getObject(): modMenu
    {
        return $this->prepareObject(true);
    }

    public function getBuildObject(): modMenu
    {
        return $this->prepareObject();
    }

}
