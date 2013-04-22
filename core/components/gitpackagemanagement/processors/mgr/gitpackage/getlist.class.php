<?php
/**
 * Get list Items
 *
 * @package gitpackagemanagement
 * @subpackage processors
 */
class GitPackageManagementGetListProcessor extends modObjectGetListProcessor {
    public $classKey = 'GitPackage';
    public $languageTopics = array('gitpackagemanagement:default');
    public $defaultSortField = 'id';
    public $defaultSortDirection = 'desc';
    public $objectType = 'gitpackagemanagement.packages';

    public function prepareQueryBeforeCount(xPDOQuery $c) {
        $this->modx->chromephp->log('test log');
        $this->modx->chromephp->warn($this->modx->getObject('modResource', 1));
        $this->modx->chromephp->error('error log', 'next error log');
        $query = $this->getProperty('query');
        if (!empty($query)) {
            $c->where(array(
                    'name:LIKE' => '%'.$query.'%',
                    'OR:description:LIKE' => '%'.$query.'%',
                ));
        }

        return $c;
    }
}
return 'GitPackageManagementGetListProcessor';