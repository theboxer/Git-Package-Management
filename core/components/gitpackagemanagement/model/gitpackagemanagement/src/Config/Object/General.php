<?php
namespace GPM\Config\Object;

use GPM\Config\ConfigObject;

class General extends ConfigObject
{
    protected $name;
    protected $lowCaseName;
    protected $description = '';
    protected $author;
    protected $version;
    
    protected $section = 'General';
    protected $validations = ['name', 'author', 'version'];

    protected function setDefaults($config)
    {
        if (!isset($config['lowCaseName'])) {
            $this->lowCaseName = str_replace(' ', '_', strtolower($this->name));
        }    
    }

    public function toArray()
    {
        return [
            'name' => $this->getName(),
            'lowCaseName' => $this->getLowCaseName(),
            'description' => $this->getDescription(),
            'author' => $this->getAuthor(),
            'version' => $this->getVersion()
        ];
    }
    
    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getLowCaseName()
    {
        return $this->lowCaseName;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return mixed
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @return mixed
     */
    public function getVersion()
    {
        return $this->version;
    }


}