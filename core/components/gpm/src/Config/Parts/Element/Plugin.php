<?php
namespace GPM\Config\Parts\Element;

use GPM\Config\Config;
use GPM\Config\Parts\Element\PluginEvent;
use GPM\Config\Rules;
use MODX\Revolution\modPlugin;
use MODX\Revolution\modPluginEvent;
use MODX\Revolution\modPropertySet;

/**
 * Class Plugin
 *
 * @property-read PluginEvent[] $events
 * @property-read boolean $disabled
 *
 * @package GPM\Config\Parts\Element
 */
class Plugin extends Element
{

    /** @var PluginEvent[] */
    protected $events = [];

    /** @var bool */
    protected $disabled = false;

    /** @var string */
    protected $type = 'plugin';

    /** @var string */
    protected $extension = 'php';

    protected $rules = [
        'name' => [Rules::isString, Rules::notEmpty],
        'category' => [Rules::isArray, Rules::categoryExists],
        'file' => [Rules::isString, Rules::notEmpty, Rules::elementFileExists],
        'properties' => [
            ['rule' => Rules::isArray, 'params' => ['itemRules' => [Rules::configPart]]]
        ],
        'events' => [
            ['rule' => Rules::isArray, 'params' => ['itemRules' => [Rules::configPart]]]
        ],
        'propertySets' => [
            ['rule' => Rules::isArray, 'params' => ['itemRules' => [Rules::isString, Rules::propertySetExists]]],
            Rules::containsEventPropertySets
        ]
    ];

    protected function generator(): void
    {
        parent::generator();

        if (empty($this->disabled)) {
            $this->disabled = false;
        }
    }

    protected function setEvents(array $events): void
    {
        foreach ($events as $event) {
            $this->events[] = new PluginEvent($event, $this->config);
        }
    }

    protected function prepareObject(int $category = null, bool $update = false, bool $static = true, bool $debug = false): modPlugin
    {
        /** @var modPlugin $obj */
        $obj = parent::prepareObject($category, $update, $static, $debug);

        if (!$obj->isNew()) {
            /** @var modPluginEvent[] $oldEvents */
            $oldEvents = $obj->getMany('PluginEvents');
            foreach($oldEvents as $oldEvent){
                $oldEvent->remove();
            }
        }

        $obj->set('disabled', $this->disabled);

        if (count($this->events) > 0) {
            $events = [];

            foreach ($this->events as $event) {
                $propertySet = null;
                if (!empty($event->propertySet)) {
                    /** @var modPropertySet $propertySet */
                    $propertySet = $this->config->modx->getObject(modPropertySet::class, ['name' => $event->propertySet]);
                }

                $eventObj = $this->config->modx->newObject(modPluginEvent::class);
                $eventObj->set('event', $event->name);
                $eventObj->set('priority', $event->priority);

                if ($propertySet) {
                    $eventObj->addOne($propertySet, 'PropertySet');
                }

                $events[] = $eventObj;
            }

            $obj->addMany($events);
        }

        return $obj;
    }

    public function getObject(int $category, bool $debug = false): modPlugin
    {
        return $this->prepareObject($category, true, true, $debug);
    }

    public function getBuildObject(): modPlugin
    {
        return $this->prepareObject(null, false, false, false);
    }

    public function setConfig(Config $config): void
    {
        parent::setConfig($config);

        foreach ($this->events as $event) {
            $event->setConfig($config);
        }
    }
}
