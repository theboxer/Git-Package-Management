<?php
namespace GPM\Config\Object;

use GPM\Config\ConfigObject;

class General extends ConfigObject
{
    public $name;
    public $lowCaseName;
    public $description = '';
    public $author;
    public $version;

    protected $rules = [
        'name' => 'notEmpty',
        'author' => 'notEmpty',
        'version' => 'notEmpty'
    ];

    protected function setDefaults($config)
    {
        if (empty($config['lowCaseName'])) {
            $this->lowCaseName = str_replace(' ', '_', strtolower($this->name));
        }    
    }

    public function toArray()
    {
        return [
            'name' => $this->name,
            'lowCaseName' => $this->lowCaseName,
            'description' => $this->description,
            'author' => $this->author,
            'version' => $this->version
        ];
    }
}