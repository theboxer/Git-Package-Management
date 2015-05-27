<?php
namespace GPM\Config;

use GPM\Util\Validator;

class Category
{
    use Validator;
    
    /* @var $config Config */
    protected $config;
    protected $name;
    protected $parent = null;

    protected $section = 'Categories';
    protected $required = ['name'];

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function fromArray($config)
    {
        $this->validate($config);
        
        $this->name = $config['name'];

        if (isset($config['parent'])) {
            $currentCategories = array_keys($this->config->getCategories());
            
            if (!in_array($config['parent'], $currentCategories)) {
                throw new \Exception('Categories - parent category does not exist');
            }

            $this->parent = $config['parent'];
        }

        return true;
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
