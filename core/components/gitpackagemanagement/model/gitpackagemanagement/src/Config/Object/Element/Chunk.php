<?php
namespace GPM\Config\Object\Element;

final class Chunk extends Element
{
    protected $elementType = 'chunk';
    protected $extension = 'tpl';

    /**
     * @return \modChunk
     */
    public function prepareObject()
    {
        /** @var \modChunk $object */
        $object = $this->config->modx->newObject('modChunk');
        $object->set('name', $this->name);
        $object->set('description', $this->description);
        $object->set('snippet', file_get_contents($this->config->general->corePath . $this->filePath));

        $object->setProperties($this->properties);

        return $object;
    }
    
    public function newObject($category)
    {
        $object = $this->prepareObject();
        $object->set('static', 1);
        $object->set('static_file', '[[++' . $this->config->general->lowCaseName . '.core_path]]' . $this->filePath);
        $object->set('category', $category);
        
        $saved = $object->save();
        
        if (!$saved) {
            throw new SaveException($this);
        }
        
        return $object;
    }

    public function updateObject($category)
    {
        /** @var \modChunk $object */
        $object = $this->config->modx->getObject('modChunk', array('name' => $this->name));
        if (!$object) {
            return $this->newObject($category);
        }

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