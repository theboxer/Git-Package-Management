<?php
namespace GPM\Config\Object;

use GPM\Config\ConfigObject;

class Category extends ConfigObject
{
    public $name;
    public $parent = null;

    protected $rules = [
        'name' => 'notEmpty',
//        'parent' => 'categoryExists'
    ];

    public function toArray()
    {
        $array = [
            'name' => $this->name
        ];
        
        if (!empty($this->parent)) {
            $array['parent'] = $this->parent;
        }
        
        return $array;
    }
    
    /**
     * @return array
     */
    public function getParents()
    {
        $parents = [$this->name];

        if ($this->parent == null || $this->parent == '') return $parents;

        /** @var Category[] $categories */
        $categories = $this->config->categories;

        if (!isset($categories[$this->parent])) return $parents;
        $parent = $this->parent;
        while (isset($categories[$parent])) {
            $parents[] = $categories[$parent]->name;
            $parent = $categories[$parent]->parent;
        }

        return array_reverse($parents);
    }

    /**
     * @return Category|null
     */
    public function getParentObject()
    {
        if (empty($this->parent)) return null;

        $categories = $this->config->categories;

        if (!isset($categories[$this->parent])) return null;

        return $categories[$this->parent];
    }

}
