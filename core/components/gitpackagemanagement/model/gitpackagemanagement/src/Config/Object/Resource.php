<?php
namespace GPM\Config\Object;

use GPM\Config\ConfigObject;

class Resource extends ConfigObject
{
    public $pagetitle;
    public $alias = '';
    public $parent = 0;
    public $tvs = array();
    public $others = array();
    public $content = '';
    public $suffix = '.html';
    public $id = 0;
    public $context_key = 'web';
    public $template = null;
    public $class_key = 'modDocument';
    public $content_type = null;
    public $longtitle = '';
    public $description = '';
    public $introtext = '';
    public $published = null;
    public $isfolder = 0;
    public $richtext = null;
    public $menuindex = null;
    public $searchable = null;
    public $cacheable = null;
    public $deleted = 0;
    public $menutitle = '';
    public $hidemenu = null;
    public $hide_children_in_tree = 0;
    public $show_in_tree = 1;
    public $setAsHome = 0;

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

    public function setId($id)
    {
        $this->id = $id;
    }
}
