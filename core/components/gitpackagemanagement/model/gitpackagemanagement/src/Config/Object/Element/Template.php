<?php
namespace GPM\Config\Object\Element;

final class Template extends Element
{
    public $icon = '';
    
    protected $elementType = 'template';
    protected $extension = 'tpl';

    public function toArray()
    {
        $array = parent::toArray();

        $array['icon'] = $this->icon;
        
        return $array;
    }

    public function getObject($build = false)
    {
        /** @var \modTemplate $object */
        $object = $this->config->modx->newObject('modTemplate');
        $object->set('templatename', $this->name);
        $object->set('description', $this->description);
        $object->set('icon', $this->icon);
        
        if ($build === true) {
            $object->set('content', file_get_contents($this->config->general->corePath . $this->filePath));
        } else {
            $object->set('static', 1);
            $object->set('static_file', '[[++' . $this->config->general->lowCaseName . '.core_path]]' . $this->filePath);    
        }

        $object->setProperties($this->properties);

        return $object;
    }
}