<?php
namespace GPM\Config\Parts\Element;


use GPM\Config\Rules;
use MODX\Revolution\modTemplate;
use MODX\Revolution\modTemplateVar;
use MODX\Revolution\modTemplateVarTemplate;

/**
 * Class TemplateVar
 *
 * @property-read array $templates
 *
 * @package GPM\Config\Parts\Element
 */
class TemplateVar extends Element
{
    /** @var string */
    protected $_type = 'templateVar';

    /** @var string */
    protected $keyField = 'name';

    /** @var string */
    protected $caption = '';

    /** @var string */
    protected $defaultValue = null;

    /** @var string */
    protected $type = 'text';

    /** @var string */
    protected $inputOptionValues = '';

    /** @var string */
    protected $outputType = 'default';

    /** @var array */
    protected $inputOptions = [];

    /** @var array */
    protected $outputOptions = [];

    /** @var array */
    protected $templates = [];

    /** @var string */
    protected $sortOrder = '0';

    protected $rules = [
        'name' => [Rules::isString, Rules::notEmpty],
        'caption' => [Rules::isString],
        'description' => [Rules::isString],
        'inputOptionValues' => [Rules::isString],
        'outputType' => [Rules::isString],
        'category' => [Rules::isArray, Rules::categoryExists],
        'file' => [Rules::isString, Rules::notEmpty, Rules::elementFileExists],
        'properties' => [
            ['rule' => Rules::isArray, 'params' => ['itemRules' => [Rules::configPart]]]
        ],
        'propertySets' => [
            ['rule' => Rules::isArray, 'params' => ['itemRules' => [Rules::isString, Rules::propertySetExists]]]
        ],
        'inputOptions' => [Rules::isObject],
        'outputOptions' => [Rules::isObject],
        'templates' => [Rules::isArray, Rules::templateExists],
    ];

    protected function generator(): void {
        parent::generator();

        if ($this->defaultValue !== null && $this->content === null) {
            $this->content = $this->defaultValue;
        }
    }

    protected function prepareObject(int $category = null, bool $update = false, bool $static = false, bool $debug = false): modTemplateVar
    {
        /** @var modTemplateVar $obj */
        $obj = parent::prepareObject($category, $update, $static, $debug);

        $obj->set('caption', $this->caption);
        $obj->set('type', $this->type);
        $obj->set('rank', $this->sortOrder);
        $obj->set('display', $this->outputType);
        $obj->set('elements', $this->inputOptionValues);
        $obj->set('input_properties', $this->inputOptions);
        $obj->set('output_properties', $this->outputOptions);

        return $obj;
    }

    public function getObject(int $category, bool $debug = false): modTemplateVar
    {
        $obj = $this->prepareObject($category, true, false);

        if (!$obj->isNew()) {
            /** @var modTemplateVarTemplate[] $oldTemplateVarTemplates */
            $oldTemplateVarTemplates = $obj->getMany('TemplateVarTemplates');
            foreach($oldTemplateVarTemplates as $oldTemplateVarTemplate){
                $oldTemplateVarTemplate->remove();
            }
        }

        if (count($this->templates) > 0) {
            $templates = [];

            foreach ($this->templates as $template) {
                /** @var modTemplate | null $templateObject */
                $templateObject = $this->config->modx->getObject(modTemplate::class, ['templatename' => $template]);
                if (!$templateObject) continue;

                $templateVarTemplate = $this->config->modx->newObject(modTemplateVarTemplate::class);
                $templateVarTemplate->set('templateid', $templateObject->id);


                $templates[] = $templateVarTemplate;
            }

            $obj->addMany($templates, 'TemplateVarTemplates');
        }

        return $obj;
    }

    public function getBuildObject(): modTemplateVar
    {
        return $this->prepareObject(null, false, false);
    }
}