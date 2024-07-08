<?php
namespace GPM\Config\Parts\Fred;

use GPM\Config\Config;
use GPM\Config\Parts\Part;
use GPM\Config\Rules;

/**
 * Class Category
 *
 * @package GPM\Config\Parts\Element
 */
class Theme extends Part
{
    use Uuid;

    protected $keyField = 'uuid';


    protected function generator(): void
    {
    }

    /**
     * @return \Fred\Model\FredTheme
     */
    public function getObject()
    {
        $where = empty($this->uuid) ? ['name' => $this->config->general->name] : ['uuid' => $this->uuid];

        /** @var \Fred\Model\FredTheme $obj */
        $obj = $this->config->modx->getObject('\\Fred\\Model\\FredTheme', $where);

        if ($obj === null) {
            $obj = $this->config->modx->newObject('\\Fred\\Model\\FredTheme');
            $obj->set('name', $this->config->general->name);

            $obj->_fields['namespace'] = $this->config->general->lowCaseName;
            $obj->setDirty('namespace');

            $obj->_fields['settingsPrefix'] = $this->config->general->lowCaseName;
            $obj->setDirty('settingsPrefix');
        }

        if (!empty($this->uuid)) {
            $obj->set('uuid', $this->uuid);
        }

        $obj->set('description', $this->config->general->description);

        return $obj;
    }

    /**
     * @return \xPDO\Om\xPDOObject
     * @throws NoUuidException
     */
    public function getBuildObject()
    {
        if (empty($this->uuid)) {
            throw new NoUuidException('theme');
        }

        /** @var \Fred\Model\FredTheme $obj */
        $theme = $this->config->modx->getObject('\\Fred\\Model\\FredTheme', ['uuid' => $this->uuid]);

        $theme->set('config', []);

        return $theme;
    }
}
