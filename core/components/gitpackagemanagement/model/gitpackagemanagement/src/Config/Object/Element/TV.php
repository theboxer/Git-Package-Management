<?php
namespace GPM\Config\Object\Element;

final class TV extends Element
{
    public $caption = null;
    public $inputOptionValues = '';
    public $defaultValue = '';
    public $type = 'text';
    public $sortOrder = '0';
    public $templates = [];
    public $category;
    public $inputProperties = [];
    public $outputProperties = [];
    
    protected $elementType = 'TV';

    protected $rules = [
        'name' => 'notEmpty',
        'caption' => 'notEmpty',
//        'category' => 'categoryExists',
        'properties' => 'type:array',
        'templates' => 'type:array',
//        'file' => 'file'
    ];
    
    protected function setDefaults($config)
    {
        if (empty($config['name'])) {
            $this->name = strtolower($this->caption);
        }
    }

    public function toArray()
    {
        $array = parent::toArray();

        $array['caption'] = $this->caption;
        $array['inputOptionValues'] = $this->inputOptionValues;
        $array['defaultValue'] = $this->defaultValue;
        $array['sortOrder'] = $this->sortOrder;
        $array['templates'] = $this->templates;
        $array['inputProperties'] = $this->inputProperties;
        $array['outputProperties'] = $this->outputProperties;

        return $array;
    }

    public function getObject($build = false)
    {
        /** @var \modTemplateVar $object */
        $object = $this->config->modx->newObject('modTemplateVar');
        $object->set('name', $this->name);
        $object->set('caption', $this->caption);
        $object->set('description', $this->description);
        $object->set('type', $this->type);

        $object->set('elements', $this->inputOptionValues);
        $object->set('rank', $this->sortOrder);
        $object->set('default_text', $this->defaultValue);

        $inputProperties = $this->inputProperties;
        if (!empty($inputProperties)) {
            $object->set('input_properties', $inputProperties);
        }

        $outputProperties = $this->outputProperties;
        if (!empty($outputProperties)) {
            $object->set('output_properties', $outputProperties[0]);
        }

        $object->setProperties($this->properties);

        return $object;
    }
}