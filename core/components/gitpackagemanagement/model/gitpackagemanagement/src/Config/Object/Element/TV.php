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
}