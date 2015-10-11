<?php
namespace GPM\Config\Object\Element;

final class Chunk extends Element
{
    protected $elementType = 'chunk';
    protected $extension = 'tpl';

    public function getObject($build = false)
    {
        /** @var \modChunk $object */
        $object = $this->config->modx->newObject('modChunk');
        $object->set('name', $this->name);
        $object->set('description', $this->description);

        if ($build === true) {
            $object->set('snippet', file_get_contents($this->config->general->corePath . $this->filePath));
        } else {
            $object->set('static', 1);
            $object->set('static_file', '[[++' . $this->config->general->lowCaseName . '.core_path]]' . $this->filePath);    
        }
        
        $object->setProperties($this->properties);
        
        return $object;
    }
}