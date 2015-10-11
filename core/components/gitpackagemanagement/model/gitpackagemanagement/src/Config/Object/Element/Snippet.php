<?php
namespace GPM\Config\Object\Element;

final class Snippet extends Element
{
    protected $elementType = 'snippet';
    protected $extension = 'php';

    public function getObject($build = false)
    {
        /** @var \modSnippet $object */
        $object = $this->config->modx->newObject('modSnippet');
        $object->set('name', $this->name);
        $object->set('description', $this->description);
        
        if ($build === true) {
            $object->set('snippet', file_get_contents($this->config->general->corePath . $this->filePath));
        } else {
            if ($this->config->gpm->getOption('enable_debug')) {
                $object->set('snippet', 'return include("' . $this->config->modx->getOption($this->config->general->lowCaseName . '.core_path') . $this->filePath . '");');
                $object->set('static', 0);
                $object->set('static_file', '');
            } else {
                $object->set('snippet', '');
                $object->set('static', 1);
                $object->set('static_file', '[[++' . $this->config->general->lowCaseName . '.core_path]]' . $this->filePath);
            }
        }
        
        $object->setProperties($this->properties);
        
        return $object;
    }
}