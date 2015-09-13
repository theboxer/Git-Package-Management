<?php
namespace GPM\Config\Object;

use GPM\Config\ConfigObject;

class Database extends ConfigObject
{
    public $prefix = 'modx_';
    public $tables = [];
    public $simpleObjects = [];
    
    protected $section ='Database';
    protected $validations = ['tables:array', 'simpleObjects:array'];

    public function toArray()
    {
        return [
            'prefix' => $this->prefix,
            'tables' => $this->tables,
            'simpleObjects' => $this->simpleObjects
        ];
    }
}