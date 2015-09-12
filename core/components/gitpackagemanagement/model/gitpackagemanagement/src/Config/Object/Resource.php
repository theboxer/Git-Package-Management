<?php
namespace GPM\Config\Object;

use GPM\Config\ConfigObject;

class Resource extends ConfigObject
{
    protected $pagetitle;
    protected $alias = '';
    protected $parent = 0;
    protected $tvs = array();
    protected $others = array();
    protected $content = '';
    protected $suffix = '.html';
    protected $id = 0;
    protected $context_key = 'web';
    protected $template = null;
    protected $class_key = 'modDocument';
    protected $content_type = null;
    protected $longtitle = '';
    protected $description = '';
    protected $introtext = '';
    protected $published = null;
    protected $isfolder = 0;
    protected $richtext = null;
    protected $menuindex = null;
    protected $searchable = null;
    protected $cacheable = null;
    protected $deleted = 0;
    protected $menutitle = '';
    protected $hidemenu = null;
    protected $hide_children_in_tree = 0;
    protected $show_in_tree = 1;
    protected $setAsHome = 0;

    protected $section = 'Resources';
    protected $validations = ['pagetitle', 'tvs:array', 'others:array'];

    protected function setDefaults($config)
    {
        if (!isset($config['alias'])) {
            $this->alias = \modResource::filterPathSegment($this->config->modx, $this->pagetitle);
        }

        if (!isset($config['content']) && !isset($config['file'])) {
            $file = $this->config->getPackagePath();
            $file .= '/core/components/' . $this->config->general->getLowCaseName() . '/resources/' . $this->alias . $this->suffix;

            if (file_exists($file)) {
                $this->content = file_get_contents($file);
            }
        } else {
            if (isset($config['content'])) {
                $this->content = $config['content'];
            }

            if (isset($config['file'])) {
                $file = $this->config->getPackagePath();
                $file .= '/core/components/' . $this->config->general->getLowCaseName() . '/resources/' . $config['file'];

                if (file_exists($file)) {
                    $this->content = file_get_contents($file);
                }
            }
        }
    }

    public function setSetAsHome($setAsHome)
    {
        $this->setAsHome = intval($setAsHome);
    }
    
    public function setPublished($published)
    {
        $this->published = intval($published);
    }
    
    public function setIsfolder($isFolder)
    {
        $this->isfolder = intval($isFolder);
    }
    
    public function setRichtext($richtext)
    {
        $this->richtext = intval($richtext);
    }
    
    public function setMenuindex($menuindex)
    {
        $this->menuindex = intval($menuindex);
    }
    
    public function setSearchable($searchable)
    {
        $this->searchable = intval($searchable);
    }
    
    public function setCacheable($cacheable)
    {
        $this->cacheable = intval($cacheable);
    }
    
    public function setDeleted($deleted)
    {
        $this->deleted = intval($deleted);
    }
    
    public function setHidemenu($hidemenu)
    {
        $this->hidemenu = intval($hidemenu);
    }

    public function setHide_children_in_tree($hide_children_in_tree)
    {
        $this->hide_children_in_tree = intval($hide_children_in_tree);
    }
    
    public function setShow_in_tree($show_in_tree)
    {
        $this->show_in_tree = intval($show_in_tree);
    }

    public function setTvs($tvs)
    {
        foreach ($tvs as $tv) {
            if (!isset($tv['name'])) {
                throw new \Exception('Resources - TV - name is not set');
            }

            if (!isset($tv['value'])) {
                $tv['value'] = '';
            }

            if (isset($tv['file'])) {
                $file = $this->config->getPackagePath();
                $file .= '/core/components/' . $this->config->general->getLowCaseName() . '/resources/' . $tv['file'];

                if (file_exists($file)) {
                    $tv['value'] = file_get_contents($file);
                }
            }

            $this->tvs[$tv['name']] = $tv;
        }
    }

    public function setOthers($others)
    {
        foreach ($others as $other) {
            if (!isset($other['name'])) {
                throw new \Exception('Resources - Other - name is not set');
            }

            if (!isset($other['value'])) {
                $other['value'] = '';
            }

            $this->others[] = $other;
        }
    }

    public function toArray()
    {
        $resource = [];

        $resource['pagetitle'] = $this->pagetitle;
        $resource['alias'] = $this->alias;

        if (is_string($this->parent)) {
            $rmf = $this->config->getAssetsFolder() . 'resourcemap.php';

            if (is_readable($rmf)) {
                $map = include $rmf;
            } else {
                $map = [];
            }

            if (isset($map[$this->parent])) {
                $resource['parent'] = $map[$this->parent];
            } else {
                /** @var \modResource $parent */
                $parent = $this->config->modx->getObject('modResource', ['pagetitle' => $this->parent]);
                if ($parent) {
                    $resource['parent'] = $parent->id;
                }
            }
        } else {
            if ($this->parent != 0) {
                /** @var \modResource $parent */
                $parent = $this->config->modx->getObject('modResource', ['id' => $this->parent]);
                if ($parent) {
                    $resource['parent'] = $parent->id;
                }
            } else {
                $resource['parent'] = 0;
            }
        }

        $resource['content'] = $this->content;
        $resource['context_key'] = $this->context_key;
        $resource['class_key'] = $this->class_key;
        $resource['longtitle'] = $this->longtitle;
        $resource['description'] = $this->description;
        $resource['isfolder'] = $this->isfolder;
        $resource['introtext'] = $this->introtext;
        $resource['deleted'] = $this->deleted;
        $resource['menutitle'] = $this->menutitle;
        $resource['hide_children_in_tree'] = $this->hide_children_in_tree;
        $resource['show_in_tree'] = $this->show_in_tree;

        if ($this->setAsHome == 1) {
            $id = $this->config->modx->getOption('site_start');

            $rmf = $this->config->getAssetsFolder() . 'resourcemap.php';

            if (is_readable($rmf)) {
                $resourceMap = include $rmf;
            } else {
                $resourceMap = [];
            }

            if (!isset($resourceMap[$this->pagetitle])) {
                $resourceMap[$this->pagetitle] = $id;
            }

            file_put_contents($rmf, '<?php return ' . var_export($resourceMap, true) . ';');

            $this->id = $id;
        }

        if ($this->id > 0) {
            $resource['id'] = $this->id;
        }

        foreach ($this->others as $other) {
            $resource[$other['name']] = $other['value'];
        }

        if ($this->template !== null) {
            if ($this->template !== 0) {
                $template = $this->config->modx->getObject('modTemplate', ['templatename' => $this->template]);
                if ($template) {
                    $resource['template'] = $template->id;
                }
            } else {
                $resource['template'] = 0;
            }
        }

        if ($this->content_type !== null) {
            $content_type = $this->config->modx->getObject('modContentType', ['name' => $this->content_type]);
            if ($content_type) {
                $resource['content_type'] = $content_type->id;
            }
        } else {
            $resource['content_type'] = $this->config->modx->getOption('default_content_type', null, 1);
        }

        if ($this->published !== null) {
            $resource['published'] = $this->published;
        }

        if ($this->menuindex !== null) {
            $resource['menuindex'] = $this->menuindex;
        }

        if ($this->hidemenu !== null) {
            $resource['hidemenu'] = $this->hidemenu;
        }

        if ($this->cacheable !== null) {
            $resource['cacheable'] = $this->cacheable;
        }

        if ($this->searchable !== null) {
            $resource['searchable'] = $this->searchable;
        }

        if ($this->richtext !== null) {
            $resource['richtext'] = $this->richtext;
        }

        return $resource;
    }

    public function toRawArray()
    {
        $resource = [];

        $resource['pagetitle'] = $this->pagetitle;
        $resource['alias'] = $this->alias;
        $resource['parent'] = $this->parent;
        $resource['content'] = $this->content;
        $resource['context_key'] = $this->context_key;
        $resource['class_key'] = $this->class_key;
        $resource['longtitle'] = $this->longtitle;
        $resource['description'] = $this->description;
        $resource['isfolder'] = $this->isfolder;
        $resource['introtext'] = $this->introtext;
        $resource['deleted'] = $this->deleted;
        $resource['menutitle'] = $this->menutitle;
        $resource['hide_children_in_tree'] = $this->hide_children_in_tree;
        $resource['show_in_tree'] = $this->show_in_tree;
        $resource['set_as_home'] = $this->setAsHome;
        $resource['tvs'] = $this->tvs;

        foreach ($this->others as $other) {
            $resource[$other['name']] = $other['value'];
        }

        $resource['template'] = $this->template;

        if ($this->content_type !== null) {
            $resource['content_type'] = $this->content_type;
        }

        if ($this->published !== null) {
            $resource['published'] = $this->published;
        }

        if ($this->menuindex !== null) {
            $resource['menuindex'] = $this->menuindex;
        }

        if ($this->hidemenu !== null) {
            $resource['hidemenu'] = $this->hidemenu;
        }

        if ($this->cacheable !== null) {
            $resource['cacheable'] = $this->cacheable;
        }

        if ($this->searchable !== null) {
            $resource['searchable'] = $this->searchable;
        }

        if ($this->richtext !== null) {
            $resource['richtext'] = $this->richtext;
        }

        return $resource;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @return mixed
     */
    public function getPagetitle()
    {
        return $this->pagetitle;
    }

    /**
     * @return int|string
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @return array
     */
    public function getTvs()
    {
        return $this->tvs;
    }

    /**
     * @return null
     */
    public function getCacheable()
    {
        return $this->cacheable;
    }

    /**
     * @return string
     */
    public function getClassKey()
    {
        return $this->class_key;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        return $this->content_type;
    }

    /**
     * @return string
     */
    public function getContextKey()
    {
        return $this->context_key;
    }

    /**
     * @return int
     */
    public function getDeleted()
    {
        return $this->deleted;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return int
     */
    public function getHideChildrenInTree()
    {
        return $this->hide_children_in_tree;
    }

    /**
     * @return null
     */
    public function getHidemenu()
    {
        return $this->hidemenu;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getIntrotext()
    {
        return $this->introtext;
    }

    /**
     * @return int
     */
    public function getIsfolder()
    {
        return $this->isfolder;
    }

    /**
     * @return string
     */
    public function getLongtitle()
    {
        return $this->longtitle;
    }

    /**
     * @return null
     */
    public function getMenuindex()
    {
        return $this->menuindex;
    }

    /**
     * @return string
     */
    public function getMenutitle()
    {
        return $this->menutitle;
    }

    /**
     * @return array
     */
    public function getOthers()
    {
        return $this->others;
    }

    /**
     * @return null
     */
    public function getPublished()
    {
        return $this->published;
    }

    /**
     * @return null
     */
    public function getRichtext()
    {
        return $this->richtext;
    }

    /**
     * @return null
     */
    public function getSearchable()
    {
        return $this->searchable;
    }

    /**
     * @return int
     */
    public function getShowInTree()
    {
        return $this->show_in_tree;
    }

    /**
     * @return string
     */
    public function getSuffix()
    {
        return $this->suffix;
    }

    /**
     * @return null
     */
    public function getTemplate()
    {
        return $this->template;
    }

    public function setId($id)
    {
        $this->id = $id;
    }
}
