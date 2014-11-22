<?php

class GitPackageConfigResource {
    private $modx;
    /* @var $config GitPackageConfig */
    private $config;

    private $pagetitle;
    private $alias = '';
    private $parent = 0;
    private $tvs = array();
    private $others = array();
    private $content = '';
    private $suffix = '.html';
    private $id = 0;
    private $context_key = 'web';

    public function __construct(modX &$modx, $gitPackageConfig) {
        $this->modx =& $modx;
        $this->config = $gitPackageConfig;
    }

    public function fromArray($config) {
        if (isset($config['pagetitle'])) {
            $this->pagetitle = $config['pagetitle'];
        } else {
            $this->modx->log(MODx::LOG_LEVEL_ERROR, '[GitPackageManagement] Resources - pagetitle is not set');
            return false;
        }

        if (isset($config['alias'])) {
            $this->alias = $config['alias'];
        } else {
            $res = new modResource($this->modx);
            $this->alias = $res->cleanAlias($this->pagetitle);
        }

        if (isset($config['parent'])) {
            $this->parent = $config['parent'];
        }

        if (isset($config['parent'])) {
            $this->parent = $config['parent'];
        }

        if (isset($config['suffix'])) {
            $this->suffix = $config['suffix'];
        }

        if (isset($config['context_key'])) {
            $this->context_key = $config['context_key'];
        }

        if (isset($config['tvs']) && is_array($config['tvs'])) {
            foreach ($config['tvs'] as $tv) {
                if (!isset($tv['name'])) {
                    $this->modx->log(MODx::LOG_LEVEL_ERROR, '[GitPackageManagement] Resources - TV - name is not set');
                    return false;
                }

                if (!isset($tv['value'])) {
                    $tv['value'] = '';
                }

                if (isset($tv['file'])) {
                    $file = $this->config->getPackagePath();
                    $file .= '/core/components/'.$this->config->getLowCaseName().'/resources/' . $tv['file'];

                    if(file_exists($file)){
                        $tv['value'] = file_get_contents($file);
                    }
                }

                $this->tvs[$tv['name']] = $tv;
            }
        }

        if (isset($config['others']) && is_array($config['others'])) {
            foreach ($config['others'] as $other) {
                if (!isset($tv['name'])) {
                    $this->modx->log(MODx::LOG_LEVEL_ERROR, '[GitPackageManagement] Resources - Other - name is not set');
                    return false;
                }

                if (!isset($other['value'])) {
                    $other['value'] = '';
                }

                $this->others[] = $other;
            }
        }

        if (!isset($config['content']) && !isset($config['file'])) {
            $file = $this->config->getPackagePath();
            $file .= '/core/components/'.$this->config->getLowCaseName().'/resources/' . $this->alias . $this->suffix;

            if(file_exists($file)){
                $this->content = file_get_contents($file);
            }
        } else {
            if (isset($config['content'])) {
                $this->content = $config['content'];
            }

            if (isset($config['file'])) {
                $file = $this->config->getPackagePath();
                $file .= '/core/components/'.$this->config->getLowCaseName().'/resources/' . $config['file'];

                if(file_exists($file)){
                    $this->content = file_get_contents($file);
                }
            }
        }

        return true;
    }

    public function toArray() {
        $resource = array();

        $resource['pagetitle'] = $this->pagetitle;
        $resource['alias'] = $this->alias;

        if (is_string($this->parent)) {
            /** @var modResource $parent */
            $parent = $this->modx->getObject('modResource', array('pagetitle' => $this->parent));
            if ($parent) {
                $resource['parent'] = $parent->id;
            }
        } else {
            if ($this->parent != 0) {
                /** @var modResource $parent */
                $parent = $this->modx->getObject('modResource', array('id' => $this->parent));
                if ($parent) {
                    $resource['parent'] = $parent->id;
                }
            } else {
                $resource['parent'] = 0;
            }
        }

        $resource['content'] = $this->content;
        $resource['context_key'] = $this->context_key;

        if ($this->id > 0) {
            $resource['id'] = $this->id;
        }

        foreach ($this->others as $other) {
            $resource[$other['name']] = $other['value'];
        }

        return $resource;
    }

    /**
     * @return string
     */
    public function getAlias() {
        return $this->alias;
    }

    /**
     * @param string $alias
     */
    public function setAlias($alias) {
        $this->alias = $alias;
    }

    /**
     * @return modX
     */
    public function getModx() {
        return $this->modx;
    }

    /**
     * @param modX $modx
     */
    public function setModx($modx) {
        $this->modx = $modx;
    }

    /**
     * @return mixed
     */
    public function getPagetitle() {
        return $this->pagetitle;
    }

    /**
     * @param mixed $pagetitle
     */
    public function setPagetitle($pagetitle) {
        $this->pagetitle = $pagetitle;
    }

    /**
     * @return int|string
     */
    public function getParent() {
        return $this->parent;
    }

    /**
     * @param int|string $parent
     */
    public function setParent($parent) {
        $this->parent = $parent;
    }

    /**
     * @return array
     */
    public function getTvs() {
        return $this->tvs;
    }

    /**
     * @param array $tvs
     */
    public function setTvs($tvs) {
        $this->tvs = $tvs;
    }

    public function setId($id) {
        $this->id = $id;
    }

}
