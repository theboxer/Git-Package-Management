<?php

namespace GitPackageManagement\Processors\GitPackage;

use MODX\Revolution\Processors\Model\GetListProcessor;
use GitPackageManagement\Model\GitPackage;
use xPDO\Om\xPDOObject;
use xPDO\Om\xPDOQuery;

class GetList extends GetListProcessor
{

    public $classKey = GitPackage::class;

    public $languageTopics = ['gitpackagemanagement:default'];

    public $defaultSortField = 'updatedon';

    public $defaultSortDirection = 'desc';

    public $objectType = 'gitpackagemanagement.packages';

    public function prepareQueryBeforeCount(xPDOQuery $c)
    {
        $query = $this->getProperty('query');
        if (!empty($query)) {
            $c->where(
                [
                    'name:LIKE'           => '%' . $query . '%',
                    'OR:description:LIKE' => '%' . $query . '%',
                ]
            );
        }
        return $c;
    }

    public function prepareRow(xPDOObject $object)
    {
        $ta = $object->toArray();

        $ta['updatedon'] = !empty($ta['updatedon']) ? strftime('%Y-%m-%d %H:%M:%S', $ta['updatedon']) : '';

        return $ta;
    }

}
