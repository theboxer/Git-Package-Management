<?php
namespace GPM\Config\Parts\Element;

use MODX\Revolution\modTemplateVar;
use GPM\Config\Rules;

/**
 * Class TemplateVar
 *
 * @property-read string $icon
 *
 * @package GPM\Config\Parts\Element
 */
class TemplateVar extends Element
{
    /** @var string */
    protected $keyField = 'name';

    /** @var string */
    protected $name = '';

    /** @var string */
    protected $caption = '';

    /** @var string */
    protected $type = 'templateVar';

    /** @var string */
    protected $tvtype = 'text';

    /** @var string */
    protected $description = '';

    /** @var array */
    protected $templates = [];

    /** @var array */
    protected $category = [];

    /** @var string */
    protected $defaultValue = '';

    /** @var string */
    protected $display = '';

    /** @var string */
    protected $sortOrder = '0';

    /** @var array */
    protected $inputProperties = [];

    /** @var array */
    protected $outputProperties = [];

    /** @var array */
    protected $inputOptionValues = '';

    /** @var integer */
    protected $locked = 0;

    /** @var integer */
    protected $source = 0;

    /** @var integer */
    protected $propertyPreprocess = 0;
 
    /** @var integer */
    protected $editorType = 0;

    protected $rules = [
        'name' => [Rules::isString, Rules::notEmpty],
        'caption' => [Rules::isString],
        // 'type' => [Rules::isString],
        'description' => [Rules::isString],
        'templates' => [Rules::isArray],
        'category' => [Rules::isArray, Rules::categoryExists],
        'properties' => [
            ['rule' => Rules::isArray, 'params' => ['itemRules' => [Rules::configPart]]]
        ],
    ];

    protected function generator(): void {

    }

    protected function prepareObject(int $category = null, bool $update = false, bool $static = false, bool $debug = false): modTemplateVar
    {
        /** @var modTemplate $obj */
        $obj = parent::prepareObject($category, $update, $static, $debug);

        // $obj->set('icon', $this->icon);

        $obj->set('caption', $this->caption);
        $obj->set('type', $this->tvtype);

        $obj->set('elements', (!empty($this->inputOptionValues)) ? $this->inputOptionValues : null);
        $obj->set('rank', $this->sortOrder);
        $obj->set('default_text', $this->defaultValue);
        $obj->set('display', $this->display);
        $obj->set('locked', $this->locked);
        $obj->set('source', $this->source);
        $obj->set('editor_type', $this->editorType);

        return $obj;
    }

    public function getObject(int $category, bool $debug = false): modTemplateVar
    {
        return $this->prepareObject($category, true, false);
    }

    public function getBuildObject(): modTemplateVar
    {
        return $this->prepareObject(null, false, false);
    }
}
