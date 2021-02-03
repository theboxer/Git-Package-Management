<?php
namespace GPM\Config\Parts\Element;

use MODX\Revolution\modTemplate;

/**
 * Class General
 *
 * @property-read string $icon
 *
 * @package GPM\Config\Parts
 */
class Template extends Element
{
    /** @var string */
    protected $icon = '';

    /** @var string */
    protected $type = 'template';

    /** @var string */
    protected $extension = 'html';

    protected function prepareObject(int $category = null, bool $update = false, bool $static = true, bool $debug = false): modTemplate
    {
        /** @var modTemplate $obj */
        $obj = parent::prepareObject($category, $update, $static, $debug);

        $obj->set('icon', $this->icon);

        return $obj;
    }

    public function getObject(int $category, bool $debug = false): modTemplate
    {
        return $this->prepareObject($category, true, true);
    }

    public function getBuildObject(): modTemplate
    {
        return $this->prepareObject(null, false, false);
    }
}
