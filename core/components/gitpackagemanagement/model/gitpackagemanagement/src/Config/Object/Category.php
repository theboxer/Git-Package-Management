<?php
namespace GPM\Config\Object;

use GPM\Config\ConfigObject;

class Category extends ConfigObject
{
    public $name;
    public $parent = null;

    protected $rules = [
        'name' => 'notEmpty',
        'parent' => 'categoryExists'
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

    public function prepareObject()
    {
        /** @var \modCategory $object */
        $object = $this->config->modx->newObject('modCategory');
        $object->set('category', $this->name);

        return $object;
    }
    
    public function newObject()
    {
        /** @var \modCategory $object */
        $object = $this->config->modx->newObject('modCategory');
        $object->set('category', $this->name);

        /** @var \modCategory $mainCategory */
        $mainCategory = $this->config->modx->getObject('modCategory', array('category' => $this->config->general->name));

        $parent = $this->getParentObject();
        if (!empty($parent)) {
            $catId = $this->config->gpm->findCategory($parent->getParents(), $mainCategory->id);
            /** @var \modCategory $parentObject */
            $parentObject = $this->config->modx->getObject('modCategory', $catId);
            if ($parentObject) {
                $parent = $parentObject->id;
            } else {
                $parent = $mainCategory->id;
            }
        } else {
            $parent = $mainCategory->id;
        }
    
        $object->set('parent', $parent);

        $saved = $object->save();

        if (!$saved) {
            throw new SaveException($this, "Couldn't save Category: {$this->name}");
        }
        
        return $object;
    }

}
