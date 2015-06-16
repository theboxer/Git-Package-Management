<?php
namespace GPM\Config\Object\Element;

final class TV extends Element
{
    protected $elementType = 'TV';
    protected $caption = null;
    protected $inputOptionValues = '';
    protected $defaultValue = '';
    protected $type = 'text';
    protected $sortOrder = '0';
    protected $templates = [];
    protected $category;
    protected $inputProperties = [];
    protected $outputProperties = [];

    protected $section = 'Elements: TV';
    protected $validations = ['caption', 'templates:array', 'category:categoryExists'];
    
    protected function setDefaults($config)
    {
        if (empty($config['name'])) {
            $this->name = strtolower($this->caption);
        }
    }

    public function toArray()
    {
        $array = parent::toArray();

        $array['caption'] = $this->getCaption();
        $array['inputOptionValues'] = $this->getInputOptionValues();
        $array['defaultValue'] = $this->getDefaultValue();
        $array['sortOrder'] = $this->getSortOrder();
        $array['templates'] = $this->getTemplates();
        $array['inputProperties'] = $this->getInputProperties();
        $array['outputProperties'] = $this->getOutputProperties();

        return $array;
    }

    /**
     * @return null
     */
    public function getCaption()
    {
        return $this->caption;
    }

    /**
     * @return string
     */
    public function getInputOptionValues()
    {
        return $this->inputOptionValues;
    }

    /**
     * @return int
     */
    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    /**
     * @return string
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @return string
     */
    public function getInputType()
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function getTemplates()
    {
        return $this->templates;
    }

    public function getInputProperties()
    {
        return $this->inputProperties;
    }

    public function getOutputProperties()
    {
        return $this->outputProperties;
    }

    /**
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }
}