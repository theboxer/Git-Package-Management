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

    /**
     * @return \modTemplate
     */
    public function prepareObject()
    {
        /** @var \modTemplate $object */
        $object = $this->config->modx->newObject('modTemplate');
        $object->set('templatename', $this->name);
        $object->set('description', $this->description);
        $object->set('icon', $this->icon);

        $object->set('content', file_get_contents($this->config->general->corePath . $this->filePath));

        $object->setProperties($this->properties);

        return $object;
    }


    public function newObject($category)
    {
        $object = $this->prepareObject();
        $object->set('category', $category);
        $object->set('static', 1);
        $object->set('static_file', '[[++' . $this->config->general->lowCaseName . '.core_path]]' . $this->filePath);    

        $saved = $object->save();

        if (!$saved) {
            throw new SaveException($this);
        }
        
        return $object;
    }

    public function updateObject($category)
    {
        /** @var \modTemplate $object */
        $object = $this->config->modx->getObject('modTemplate', array('templatename' => $this->name));
        if (!$object) {
            return $this->newObject($category);
        }
        
        $object->set('icon', $this->icon);
        $object->set('static', 1);
        $object->set('static_file', '[[++' . $this->config->general->lowCaseName . '.core_path]]' . $this->filePath);
        $object->set('description', $this->description);
        $object->set('category', $category);
        $object->setProperties($this->properties);
        
        $saved = $object->save();

        if (!$saved) {
            throw new SaveException($this);
        }
        
        return $object;
    }
}