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
    public $defaultSortField = 'updatedon';
    public $defaultSortDirection = 'desc';
    public $objectType = 'gitpackagemanagement.packages';

    public function prepareQueryBeforeCount(xPDOQuery $c) {
        $query = $this->getProperty('query');
        if (!empty($query)) {
            $c->where(array(
                    'name:LIKE' => '%'.$query.'%',
                    'OR:description:LIKE' => '%'.$query.'%',
                ));
        }
        return $c;
    }

    public function prepareRow(xPDOObject $object)
    {
        $ta = $object->toArray();
        $ta['updatedon'] = !empty($ta['updatedon']) ?
            strftime('%Y-%m-%d %H:%M:%S', $ta['updatedon']) : '';
        return $ta;
    }
}
return 'GitPackageManagementGetListProcessor';
