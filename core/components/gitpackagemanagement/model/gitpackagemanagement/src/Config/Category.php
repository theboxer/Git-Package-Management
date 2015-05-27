<?php
namespace GPM\Config;

class Category
{
    private $modx;
    /* @var $config Config */
    private $config;
    private $name;
    private $parent = null;

    public function __construct(\modX &$modx, $gitPackageConfig)
    {
        $this->modx =& $modx;
        $this->config = $gitPackageConfig;
    }

    public function fromArray($config)
    {
        if (isset($config['name'])) {
            $this->name = $config['name'];
        } else {
            throw new \Exception('Categories - name is not set');
        }

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
        $parents = array($this->name);

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
