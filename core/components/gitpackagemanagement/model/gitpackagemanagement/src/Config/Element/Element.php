<?php
namespace GPM\Config\Element;

use GPM\Config\Config;
use GPM\Util\Validator;

abstract class Element
{
    use Validator;
    
    /** @var Config $config */
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
    protected $properties = [];
    /** @var string $category */
    protected $category;
    /** @var string $filePath */
    protected $filePath;
    
    protected $section = 'Elements';
    protected $validations = ['name', 'category:categoryExists'];

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function fromArray($config)
    {
        $this->validate($config);
        
        $this->name = $config['name'];

        if (isset($config['description'])) {
            $this->description = $config['description'];
        }

        if (isset($config['file'])) {
            $this->file = $config['file'];
        } else {
            $this->file = $this->name . '.' . $this->type . '.' . $this->extension;
        }

        if (isset($config['properties']) && is_array($config['properties'])) {
            $this->setProperties($config['properties']);
        }

        if (isset($config['category'])) {
            $this->category = $config['category'];
        }

        if ($this->checkFile() == false) {
            return false;
        }

        return true;
    }

    protected function checkFile()
    {
        $filePaths = [
            $this->file,
            strtolower($this->file),
        ];

        if (!empty($this->category)) {
            $categories = $this->config->getCategories();
            $categoryPath = '/' . str_replace(' ', '_', implode('/', $categories[$this->category]->getParents())) . '/';

            $filePaths[] = $categoryPath . $this->file;
            $filePaths[] = strtolower($categoryPath . $this->file);
        }
        
        $file = $this->config->getPackagePath();
        $file .= '/core/components/' . $this->config->getLowCaseName() . '/elements/' . $this->type . 's/';

        $exists = false;
        foreach ($filePaths as $filePath) {
            $finalFile = $file . $filePath;
            if (file_exists($finalFile)) {
                $exists = $filePath;
                break;
            }
        }

        if ($exists === false) {
            throw new \Exception($this->generateMsg('file', $finalFile . ' file does not exists'));
        }

        $this->filePath = $exists;

        return true;
    }

    public function getFile()
    {
        return $this->file;
    }

    public function getFilePath()
    {
        return $this->filePath;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getProperties()
    {
        return $this->properties;
    }

    public function getDescription()
    {
        return $this->description;
    }

    protected function setProperties($properties)
    {
        foreach ($properties as $property) {
            $prop = [];

            if (isset($property['name'])) {
                $prop['name'] = $property['name'];
            } else {
                throw new \Exception('Elements: ' . $this->type . ' - properties: names is required');
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
                $prop['options'] = $property['options'];
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