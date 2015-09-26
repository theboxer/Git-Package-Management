<?php
namespace GPM\Config\Object\Element;

final class Plugin extends Element
{
    public $events = [];
    
    protected $elementType = 'plugin';
    protected $extension = 'php';

    protected $rules = [
        'name' => 'notEmpty',
//        'category' => 'categoryExists',
        'properties' => 'type:array,null',
//        'file' => 'file',
        'events' => 'type:array,null|notEmpty'
    ];

    public function toArray()
    {
        $array = parent::toArray();

        $array['events'] = $this->events;

        return $array;
    }
}