<?php
namespace GPM\Config\Parts\Element;

use MODX\Revolution\modPlugin;
use MODX\Revolution\modPluginEvent;

/**
 * Class Plugin
 *
 * @property-read string[] $events
 * @property-read boolean $disabled
 *
 * @package GPM\Config\Parts\Element
 */
class Plugin extends Element
{

    /** @var string[] */
    protected $events = [];

    /** @var bool */
    protected $disabled = false;

    /** @var string */
    protected $type = 'plugin';

    /** @var string */
    protected $extension = 'php';

    protected function generator(): void
    {
        parent::generator();

        if (empty($this->disabled)) {
            $this->disabled = false;
        }
    }

    /**
     * @param bool|string $disabled
     *
     * @return bool
     */
    protected function setDisabled($disabled): bool
    {
        if (empty($disabled)) return false;
        if (is_bool($disabled)) return $disabled;

        return intval($disabled) === 1;
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
                $eventObj = $this->config->modx->newObject(modPluginEvent::class);
                $eventObj->fromArray(
                    [
                        'event'       => $event,
                        'priority'    => 0,
                        'propertyset' => 0,
                    ],
                    '',
                    true,
                    true
                );

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
}
