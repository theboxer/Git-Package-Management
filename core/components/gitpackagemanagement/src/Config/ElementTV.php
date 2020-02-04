<?php
namespace GitPackageManagement\Config;

class ElementTV extends Element{
    protected $type = 'TV';
    protected $caption = null;
    protected $inputOptionValues = '';
    protected $defaultValue = '';
    protected $inputType = 'text';
    protected $sortOrder = '0';
    protected $templates = array();
    protected $category;
    protected $display = '';
    private $inputProperties = array();
    private $outputProperties = array();

    public function fromArray($config) {
        if(isset($config['caption'])){
            $this->caption = $config['caption'];
        }else{
            $this->config->error->addError('Elements: ' . $this->type . ' - caption is not set', true);
            return false;
        }

        if(isset($config['inputOptionValues'])){
            $this->inputOptionValues = $config['inputOptionValues'];
        }

        if(isset($config['defaultValue'])){
            $this->defaultValue = $config['defaultValue'];
        }

        if(isset($config['name'])){
            $this->name = $config['name'];
        }else{
            $this->name = strtolower($this->caption);
        }

        if (isset($config['description'])) {
            $this->description = $config['description'];
        }

        if (isset($config['properties']) && is_array($config['properties'])) {
            $propertiesSet = $this->setProperties($config['properties']);
            if ($propertiesSet === false) return false;
        }

        if(isset($config['type'])){
            $this->inputType = $config['type'];
        }

        if(isset($config['inputProperties'])){
            $this->inputProperties = $config['inputProperties'];
        }

        if(isset($config['outputProperties'])){
            $this->outputProperties = $config['outputProperties'];
        }

        if(isset($config['sortOrder'])){
            $this->sortOrder = $config['sortOrder'];
        }

        if(isset($config['display'])){
            $this->display = $config['display'];
        }

        if(isset($config['templates'])){
            if(is_array($config['templates'])){
                $this->templates = $config['templates'];
            }else{
                $this->config->error->addError('Elements: ' . $this->type . ' - templates are not an array', true);
                return false;
            }
        }

        if (isset($config['category'])) {
            $currentCategories = array_keys($this->config->getCategories());
            if (!in_array($config['category'], $currentCategories)) {
                $this->config->error->addError('Elements: ' . $this->type . ' - category: ' . $config['category'] . ' does not exist', true);
                return false;
            }

            $this->category = $config['category'];
        }

        return true;
    }

    /**
     * @return null
     */
    public function getCaption() {
        return $this->caption;
    }

    /**
     * @return string
     */
    public function getInputOptionValues() {
        return $this->inputOptionValues;
    }

    /**
     * @return int
     */
    public function getSortOrder() {
        return $this->sortOrder;
    }

    /**
     * @return string
     */
    public function getDefaultValue() {
        return $this->defaultValue;
    }

    /**
     * @return string
     */
    public function getInputType() {
        return $this->inputType;
    }

    /**
     * @return array
     */
    public function getTemplates() {
        return $this->templates;
    }

    public function getInputProperties() {
        return $this->inputProperties;
    }

    public function getOutputProperties() {
        return $this->outputProperties;
    }

    /**
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    public function getDisplay()
    {
        return $this->display;
    }
}
