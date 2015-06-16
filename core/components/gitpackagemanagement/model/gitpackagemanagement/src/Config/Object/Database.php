<?php
namespace GPM\Config\Object;

use GPM\Config\ConfigObject;

class Database extends ConfigObject
{
    protected $prefix = 'modx_';
    protected $tables = [];
    protected $simpleObjects = [];
    
    protected $section ='Database';
    protected $validations = ['tables:array', 'simpleObjects:array'];

    public function toArray()
    {
        // @TODO
        return [];
    }
    
    public function getPrefix()
    {
        return $this->prefix;
    }

    public function getTables()
    {
        return $this->tables;
    }

    public function getSimpleObjects()
    {
        return $this->simpleObjects;
    }

}