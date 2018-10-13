<?php

class GitPackageConfigCategory
{
    private $modx;
    /* @var $gitPackageConfig GitPackageConfig */
    private $config;
    private $name;
    private $rank;
    private $parent = null;

    public function __construct(modX &$modx, $gitPackageConfig)
    {
        $this->modx =& $modx;
        $this->config = $gitPackageConfig;
    }

    public function fromArray($config)
    {
        if(isset($config['name'])){
            $this->name = $config['name'];
        }else{
            $this->config->error->addError('Categories - name is not set', true);
            return false;
        }

        if(isset($config['parent'])) {
            $currentCategories = array_keys($this->config->getCategories());
            if (!in_array($config['parent'], $currentCategories)) {
                $this->config->error->addError('Categories - parent category does not exist', true);
                return false;
            }

            $this->parent = $config['parent'];
        }

        if (isset($config['rank'])) {
            $this->rank = $config['rank'];
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
     * @return mixed
     */
    public function getRank()
    {
        return $this->rank;
    }

    /**
     * @return array
     */
    public function getParents()
    {
        $parents = array($this->name);

        if ($this->parent == null || $this->parent == '') return $parents;

        /** @var GitPackageConfigCategory[] $categories */
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
     * @return GitPackageConfigCategory|null
     */
    public function getParentObject()
    {
        if (empty($this->parent)) return null;

        $categories = $this->config->getCategories();

        if (!isset($categories[$this->parent])) return null;

        return $categories[$this->parent];
    }

}
