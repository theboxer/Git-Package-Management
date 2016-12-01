<?php
namespace GPM\Config\Object\Element;

final class Snippet extends Element
{
    protected $elementType = 'snippet';
    protected $extension = 'php';

    /**
     * @return \modSnippet
     */
    public function prepareObject()
    {
        /** @var \modSnippet $object */
        $object = $this->config->modx->newObject('modSnippet');
        $object->set('name', $this->name);
        $object->set('description', $this->description);
        $object->set('snippet', file_get_contents($this->config->general->corePath . $this->filePath));

        $object->setProperties($this->properties);

        return $object;
    }


    public function newObject($category)
    {
        $object = $this->prepareObject();
        $object->set('category', $category);
        
        if ($this->config->gpm->getOption('enable_debug')) {
            $object->set('snippet', 'return include("' . $this->config->modx->getOption($this->config->general->lowCaseName . '.core_path') . $this->filePath . '");');
            $object->set('static', 0);
            $object->set('static_file', '');
        } else {
            $object->set('snippet', '');
            $object->set('static', 1);
            $object->set('static_file', '[[++' . $this->config->general->lowCaseName . '.core_path]]' . $this->filePath);
        }
        
        $saved = $object->save();

        if (!$saved) {
            throw new SaveException($this);
        }
        
        return $object;
    }

    public function updateObject($category)
    {
        /** @var \modSnippet $object */
        $object = $this->config->modx->getObject('modSnippet', array('name' => $this->name));
        if (!$object) {
            return $this->newObject($category);
        }

        if ($this->config->gpm->getOption('enable_debug')) {
            $object->set('snippet', 'return include("' . $this->config->modx->getOption($this->config->general->lowCaseName . '.core_path') . $this->filePath . '");');
            $object->set('static', 0);
            $object->set('static_file', '');
        } else {
            $object->set('static', 1);
            $object->set('static_file', '[[++' . $this->config->general->lowCaseName . '.core_path]]' . $this->filePath);
        }

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