<?php
namespace GPM\Config\Object;

use GPM\Config\ConfigObject;

class Dependency extends ConfigObject
{
    /** @var string */
    public $name;

    /** @var string */
    public $version;

    protected $rules = [
        'name' => 'notEmpty',
        'version' => 'notEmpty'
    ];
    
    public function toArray()
    {
        // @TODO
        return [];
    }
}