<?php
namespace GPM\Config\Object\Element;

final class Plugin extends Element
{
    public $events = [];
    
    protected $elementType = 'plugin';
    protected $extension = 'php';

    protected $rules = [
        'name' => 'notEmpty',
//        'category' => 'categoryExists',
        'properties' => 'type:array,null',
//        'file' => 'file',
        'events' => 'type:array,null|notEmpty'
    ];

    public function toArray()
    {
        $array = parent::toArray();

        $array['events'] = $this->events;

        return $array;
    }

    public function getObject($build = false)
    {
        /** @var \modPlugin $object */
        $object = $this->config->modx->newObject('modPlugin');
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
                if ($this->config->gpm->getOption('enable_debug')) {
                    $object->set('plugincode', 'include("' . $this->config->modx->getOption($this->config->general->lowCaseName . '.core_path') . $this->filePath . '");');
                    $object->set('static', 0);
                    $object->set('static_file', '');
                } else {
                    $object->set('snippet', '');
                    $object->set('static', 1);
                    $object->set('static_file', '[[++' . $this->config->general->lowCaseName . '.core_path]]' . $this->filePath);
                }
            }
        }

        $object->setProperties($this->properties);

        /** @var \modPluginEvent[] $events */
        $events = [];

        foreach ($this->events as $event) {
            $events[$event] = $this->config->modx->newObject('modPluginEvent');
            $events[$event]->fromArray(array(
                'event' => $event,
                'priority' => 0,
                'propertyset' => 0,
            ), '', true, true);
        }

        $object->addMany($events);

        return $object;
    }
}