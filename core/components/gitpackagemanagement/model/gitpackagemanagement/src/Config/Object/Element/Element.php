<?php
namespace GPM\Config\Object\Element;

use GPM\Config\ConfigObject;

abstract class Element extends ConfigObject
{
    /** @var string $name */
    public $name;
    /** @var string $description */
    public $description = '';
    /** @var string $file */
    public $file;
    /** @var array $properties */
    public $properties = [];
    /** @var string $category */
    public $category;
    /** @var string $filePath */
    public $filePath;
    
    /** @var string $elementType */
    protected $elementType;
    /** @var string $extension */
    protected $extension;

    protected $rules = [
        'name' => 'notEmpty',
//        'category' => 'categoryExists',
        'properties' => 'type:array',
//        'file' => 'file'
    ];

    protected function setDefaults($config)
    {
        if (empty($this->file)) {
            $this->file = $this->name . '.' . $this->elementType . '.' . $this->extension;
        }

        // @TODO Prettify 
        $filePaths = [
            $this->file,
            strtolower($this->file),
        ];

        if (!empty($this->category)) {
            $categories = $this->config->categories;
            $categoryPath = '/' . implode('/', $categories[$this->category]->getParents()) . '/';

            $filePaths[] = $categoryPath . $this->file;
            $filePaths[] = strtolower($categoryPath . $this->file);
        }

        $file = $this->config->packagePath;
        $file .= '/core/components/' . $this->config->general->lowCaseName . '/elements/' . $this->elementType . 's/';

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
    }

    public function toArray()
    {
        return [
            'name' => $this->name,
            'category' => $this->category,
            'description' => $this->description,
            'file' => $this->file,
            'properties' => $this->properties
        ];
    }

    protected function setProperties($properties)
    {
        foreach ($properties as $property) {
            $prop = [];

            if (isset($property['name'])) {
                $prop['name'] = $property['name'];
            } else {
                throw new \Exception('Elements: ' . $this->elementType . ' - properties: names is required');
            }

            if (isset($property['description'])) {
                $prop['desc'] = $property['description'];
            } else {
                $prop['desc'] = $this->config->general->lowCaseName . '.' . strtolower($this->name) . '.' . $prop['name'];
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
                $prop['lexicon'] = $this->config->general->lowCaseName . ':properties';
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



}