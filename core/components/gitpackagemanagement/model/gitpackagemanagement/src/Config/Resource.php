<?php
namespace GPM\Config;

use GPM\Util\Validator;

class Resource
{
    use Validator;
    
    /** @var \modX $modx */
    protected $modx;
    /* @var $config Config */
    protected $config;

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
    protected $validations = ['pagetitle'];

    public function __construct($config, \modX &$modx)
    {
        $this->modx =& $modx;
        $this->config = $config;
    }

    public function fromArray($config)
    {
        $this->validate($config);
        
        $this->pagetitle = $config['pagetitle'];

        if (isset($config['alias'])) {
            $this->alias = $config['alias'];
        } else {
            $res = new \modResource($this->modx);
            $this->alias = $res->cleanAlias($this->pagetitle);
        }

        if (isset($config['setAsHome'])) {
            $this->setAsHome = intval($config['setAsHome']);
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

        if (isset($config['template'])) {
            $this->template = $config['template'];
        }

        if (isset($config['class_key'])) {
            $this->class_key = $config['class_key'];
        }

        if (isset($config['content_type'])) {
            $this->content_type = $config['content_type'];
        }

        if (isset($config['longtitle'])) {
            $this->longtitle = $config['longtitle'];
        }

        if (isset($config['description'])) {
            $this->description = $config['description'];
        }

        if (isset($config['menutitle'])) {
            $this->menutitle = $config['menutitle'];
        }

        if (isset($config['published'])) {
            $this->published = intval($config['published']);
        }

        if (isset($config['isfolder'])) {
            $this->isfolder = intval($config['isfolder']);
        }

        if (isset($config['introtext'])) {
            $this->introtext = $config['introtext'];
        }

        if (isset($config['richtext'])) {
            $this->richtext = intval($config['richtext']);
        }

        if (isset($config['menuindex'])) {
            $this->menuindex = intval($config['menuindex']);
        }

        if (isset($config['searchable'])) {
            $this->searchable = intval($config['searchable']);
        }

        if (isset($config['cacheable'])) {
            $this->cacheable = intval($config['cacheable']);
        }

        if (isset($config['deleted'])) {
            $this->deleted = intval($config['deleted']);
        }

        if (isset($config['hidemenu'])) {
            $this->hidemenu = intval($config['hidemenu']);
        }

        if (isset($config['hide_children_in_tree'])) {
            $this->hide_children_in_tree = intval($config['hide_children_in_tree']);
        }

        if (isset($config['show_in_tree'])) {
            $this->show_in_tree = intval($config['show_in_tree']);
        }

        if (isset($config['tvs']) && is_array($config['tvs'])) {
            foreach ($config['tvs'] as $tv) {
                if (!isset($tv['name'])) {
                    throw new \Exception('Resources - TV - name is not set');
                }

                if (!isset($tv['value'])) {
                    $tv['value'] = '';
                }

                if (isset($tv['file'])) {
                    $file = $this->config->getPackagePath();
                    $file .= '/core/components/' . $this->config->getLowCaseName() . '/resources/' . $tv['file'];

                    if (file_exists($file)) {
                        $tv['value'] = file_get_contents($file);
                    }
                }

                $this->tvs[$tv['name']] = $tv;
            }
        }

        if (isset($config['others']) && is_array($config['others'])) {
            foreach ($config['others'] as $other) {
                if (!isset($tv['name'])) {
                    throw new \Exception('Resources - Other - name is not set');
                }

                if (!isset($other['value'])) {
                    $other['value'] = '';
                }

                $this->others[] = $other;
            }
        }

        if (!isset($config['content']) && !isset($config['file'])) {
            $file = $this->config->getPackagePath();
            $file .= '/core/components/' . $this->config->getLowCaseName() . '/resources/' . $this->alias . $this->suffix;

            if (file_exists($file)) {
                $this->content = file_get_contents($file);
            }
        } else {
            if (isset($config['content'])) {
                $this->content = $config['content'];
            }

            if (isset($config['file'])) {
                $file = $this->config->getPackagePath();
                $file .= '/core/components/' . $this->config->getLowCaseName() . '/resources/' . $config['file'];

                if (file_exists($file)) {
                    $this->content = file_get_contents($file);
                }
            }
        }

        return true;
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
                $parent = $this->modx->getObject('modResource', ['pagetitle' => $this->parent]);
                if ($parent) {
                    $resource['parent'] = $parent->id;
                }
            }
        } else {
            if ($this->parent != 0) {
                /** @var \modResource $parent */
                $parent = $this->modx->getObject('modResource', ['id' => $this->parent]);
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
            $id = $this->modx->getOption('site_start');

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
                $template = $this->modx->getObject('modTemplate', ['templatename' => $this->template]);
                if ($template) {
                    $resource['template'] = $template->id;
                }
            } else {
                $resource['template'] = 0;
            }
        }

        if ($this->content_type !== null) {
            $content_type = $this->modx->getObject('modContentType', ['name' => $this->content_type]);
            if ($content_type) {
                $resource['content_type'] = $content_type->id;
            }
        } else {
            $resource['content_type'] = $this->modx->getOption('default_content_type', null, 1);
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
     * @param string $alias
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
    }

    /**
     * @return mixed
     */
    public function getPagetitle()
    {
        return $this->pagetitle;
    }

    /**
     * @param mixed $pagetitle
     */
    public function setPagetitle($pagetitle)
    {
        $this->pagetitle = $pagetitle;
    }

    /**
     * @return int|string
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param int|string $parent
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     * @return array
     */
    public function getTvs()
    {
        return $this->tvs;
    }

    /**
     * @param array $tvs
     */
    public function setTvs($tvs)
    {
        $this->tvs = $tvs;
    }

    /**
     * @return null
     */
    public function getCacheable()
    {
        return $this->cacheable;
    }

    /**
     * @param null $cacheable
     */
    public function setCacheable($cacheable)
    {
        $this->cacheable = $cacheable;
    }

    /**
     * @return string
     */
    public function getClassKey()
    {
        return $this->class_key;
    }

    /**
     * @param string $class_key
     */
    public function setClassKey($class_key)
    {
        $this->class_key = $class_key;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        return $this->content_type;
    }

    /**
     * @param string $contentType
     */
    public function setContentType($contentType)
    {
        $this->content_type = $contentType;
    }

    /**
     * @return string
     */
    public function getContextKey()
    {
        return $this->context_key;
    }

    /**
     * @param string $context_key
     */
    public function setContextKey($context_key)
    {
        $this->context_key = $context_key;
    }

    /**
     * @return int
     */
    public function getDeleted()
    {
        return $this->deleted;
    }

    /**
     * @param int $deleted
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return int
     */
    public function getHideChildrenInTree()
    {
        return $this->hide_children_in_tree;
    }

    /**
     * @param int $hide_children_in_tree
     */
    public function setHideChildrenInTree($hide_children_in_tree)
    {
        $this->hide_children_in_tree = $hide_children_in_tree;
    }

    /**
     * @return null
     */
    public function getHidemenu()
    {
        return $this->hidemenu;
    }

    /**
     * @param null $hidemenu
     */
    public function setHidemenu($hidemenu)
    {
        $this->hidemenu = $hidemenu;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getIntrotext()
    {
        return $this->introtext;
    }

    /**
     * @param string $introtext
     */
    public function setIntrotext($introtext)
    {
        $this->introtext = $introtext;
    }

    /**
     * @return int
     */
    public function getIsfolder()
    {
        return $this->isfolder;
    }

    /**
     * @param int $isfolder
     */
    public function setIsfolder($isfolder)
    {
        $this->isfolder = $isfolder;
    }

    /**
     * @return string
     */
    public function getLongtitle()
    {
        return $this->longtitle;
    }

    /**
     * @param string $longtitle
     */
    public function setLongtitle($longtitle)
    {
        $this->longtitle = $longtitle;
    }

    /**
     * @return null
     */
    public function getMenuindex()
    {
        return $this->menuindex;
    }

    /**
     * @param null $menuindex
     */
    public function setMenuindex($menuindex)
    {
        $this->menuindex = $menuindex;
    }

    /**
     * @return string
     */
    public function getMenutitle()
    {
        return $this->menutitle;
    }

    /**
     * @param string $menutitle
     */
    public function setMenutitle($menutitle)
    {
        $this->menutitle = $menutitle;
    }

    /**
     * @return array
     */
    public function getOthers()
    {
        return $this->others;
    }

    /**
     * @param array $others
     */
    public function setOthers($others)
    {
        $this->others = $others;
    }

    /**
     * @return null
     */
    public function getPublished()
    {
        return $this->published;
    }

    /**
     * @param null $published
     */
    public function setPublished($published)
    {
        $this->published = $published;
    }

    /**
     * @return null
     */
    public function getRichtext()
    {
        return $this->richtext;
    }

    /**
     * @param null $richtext
     */
    public function setRichtext($richtext)
    {
        $this->richtext = $richtext;
    }

    /**
     * @return null
     */
    public function getSearchable()
    {
        return $this->searchable;
    }

    /**
     * @param null $searchable
     */
    public function setSearchable($searchable)
    {
        $this->searchable = $searchable;
    }

    /**
     * @return int
     */
    public function getShowInTree()
    {
        return $this->show_in_tree;
    }

    /**
     * @param int $show_in_tree
     */
    public function setShowInTree($show_in_tree)
    {
        $this->show_in_tree = $show_in_tree;
    }

    /**
     * @return string
     */
    public function getSuffix()
    {
        return $this->suffix;
    }

    /**
     * @param string $suffix
     */
    public function setSuffix($suffix)
    {
        $this->suffix = $suffix;
    }

    /**
     * @return null
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param null $template
     */
    public function setTemplate($template)
    {
        $this->template = $template;
    }

}
