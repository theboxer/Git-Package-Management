<?php
class TaggerGroupUpdateFromGridProcessor extends modObjectUpdateProcessor {
    public $classKey = 'GitPackage';
    public $languageTopics = array('gitpackagemanagement:default');
    public $objectType = 'gitpackagemanagement.package';
    /** @var GitPackage $object */
    public $object;

    public function initialize() {
        $data = $this->getProperty('data');
        if (empty($data)) return $this->modx->lexicon('invalid_data');
        $data = $this->modx->fromJSON($data);
        if (empty($data)) return $this->modx->lexicon('invalid_data');
        $this->setProperties($data);
        $this->unsetProperty('data');
        return parent::initialize();
    }

    public function beforeSave() {
        $key = trim($this->getProperty('key', ''));
        $this->object->set('key', $key);

        return parent::beforeSave();
    }
}

return 'TaggerGroupUpdateFromGridProcessor';