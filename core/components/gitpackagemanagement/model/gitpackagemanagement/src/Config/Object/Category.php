<?php
namespace GPM\Config\Object;

use GPM\Config\ConfigObject;

class Category extends ConfigObject
{
    protected $name;
    protected $parent = null;

    protected $section = 'Categories';
    protected $validations = ['name', 'parent:categoryExists'];

    public function toArray()
    {
        $array = [
            'name' => $this->getName()
        ];
        
        $parent = $this->getParent();
        if (!empty($parent)) {
            $array['parent'] = $this->getParent();
        }
        
        return $array;
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
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @return array
     */
    public function getParents()
    {
        $parents = [$this->name];

        if ($this->parent == null || $this->parent == '') return $parents;

        /** @var Category[] $categories */
        $categories = $this->config->getCategories();

        if (!isset($categories[$this->parent])) return $parents;
        $parent = $this->parent;
        while (isset($categories[$parent])) {
            $parents[] = $categories[$parent]->getName();
            $parent = $categories[$parent]->getParent();
        }

        return array_reverse($parents);
    }

    /**
     * @return Category|null
     */
    public function getParentObject()
    {
        if (empty($this->parent)) return null;

        $categories = $this->config->getCategories();

        if (!isset($categories[$this->parent])) return null;

        return $categories[$this->parent];
    }

}
