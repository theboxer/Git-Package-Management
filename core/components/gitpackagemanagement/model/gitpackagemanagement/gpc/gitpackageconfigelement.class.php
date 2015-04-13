<?php

abstract class GitPackageConfigElement{
    /** @var modX $modx */
    protected $modx;
    /** @var GitPackageConfig $config */
    protected $config;
    /** @var string $name */
    protected $name;
    /** @var string $description */
    protected $description = '';
    /** @var string $file */
    protected $file;
    /** @var string $type */
    protected $type;
    /** @var string $extension */
    protected $extension;
    /** @var array $properties */
    protected $properties = array();
    /** @var string $category */
    protected $category;
    /** @var string $filePath */
    private $filePath;

    public function __construct(modX &$modx, GitPackageConfig $config) {
        $this->modx =& $modx;
        $this->config = $config;
    }

    public function fromArray($config) {
        if(isset($config['name'])){
            $this->name = $config['name'];
        }else{
            $this->config->error->addError('Elements: ' . $this->type . ' - name is not set', true);
            return false;
        }

        if (isset($config['description'])) {
            $this->description = $config['description'];
        }

        if(isset($config['file'])){
            $this->file = $config['file'];
        } else {
            $this->file = strtolower($this->name).'.'.$this->type . '.' . $this->extension;
        }

        if (isset($config['properties']) && is_array($config['properties'])) {
            $propertiesSet = $this->setProperties($config['properties']);
            if ($propertiesSet === false) return false;
        }

        if (isset($config['category'])) {
            $currentCategories = array_keys($this->config->getCategories());
            if (!in_array($config['category'], $currentCategories)) {
                $this->config->error->addError('Elements: ' . $this->type . ' - category: ' . $config['category'] . ' does not exist', true);
                return false;
            }

            $this->category = $config['category'];
        }

        if ($this->checkFile() == false) {
            return false;
        }

        return true;
    }

    protected function checkFile() {
        $filePaths = array(
            'elements/' . $this->type . 's/' . $this->file
        );

        if (!empty($this->category)) {
            $categories = $this->config->getCategories();
            $categoryPath =  '/' . str_replace(' ', '_', strtolower(implode('/', $categories[$this->category]->getParents()))) . '/';

            $filePaths[] = 'elements/' . $this->type . 's' . $categoryPath . $this->file;
        }

        $file = $this->config->getPackagePath();
        $file .= '/core/components/'.$this->config->getLowCaseName().'/';

        $exists = false;
        foreach ($filePaths as $filePath) {
            $finalFile = $file . $filePath;
            if(file_exists($finalFile)) {
                $exists = $filePath;
                break;
            }
        }

        if($exists === false){
            $this->config->error->addError('Elements: ' . $file . ' - file does not exists', true);
            return false;
        }

        $this->filePath = $exists;

        return true;
    }

    public function getFile() {
        return $this->file;
    }

    public function getFilePath() {
        return $this->filePath;
    }

    public function getName() {
        return $this->name;
    }

    public function getProperties() {
        return $this->properties;
    }

    public function getDescription() {
        return $this->description;
    }

    protected function setProperties($properties) {
        foreach ($properties as $property) {
            $prop = array();

            if (isset($property['name'])) {
                $prop['name'] = $property['name'];
            } else {
                return false;
            }

            if (isset($property['description'])) {
                $prop['desc'] = $property['description'];
            } else {
                $prop['desc'] = $this->config->getLowCaseName() . '.' . strtolower($this->getName()) . '.' . $prop['name'];
            }

            if (isset($property['type'])) {
                $prop['type'] = $property['type'];
            } else {
                $prop['type'] = 'textfield';
            }

            if (isset($property['options'])) {
                $prop['options'] = is_array($property['options']) ? $this->modx->toJSON($property['options']) : $property['options'];
            } else {
                $prop['options'] = '';
            }

            if (isset($property['value'])) {
                $prop['value'] = $property['value'];
            } else {
                $prop['value'] = '';
            }

            if (isset($property['lexicon'])) {
                $prop['lexicon'] = $property['lexicon'];
            } else {
                $prop['lexicon'] = $this->config->getLowCaseName() . ':properties';
            }

            if (isset($property['area'])) {
                $prop['area'] = $property['area'];
            } else {
                $prop['area'] = '';
            }

            $this->properties[] = $prop;
        }

        return true;
    }

    /**
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

}
