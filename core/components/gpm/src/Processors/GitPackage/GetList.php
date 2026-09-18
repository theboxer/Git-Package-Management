<?php
namespace GPM\Processors\GitPackage;

use GPM\Model\GitPackage;
use MODX\Revolution\Processors\Model\GetListProcessor;
use xPDO\Om\xPDOObject;
use xPDO\Om\xPDOQuery;

class GetList extends GetListProcessor
{
    public $classKey = GitPackage::class;
    public $languageTopics = ['gpm:default'];
    public $defaultSortField = 'updatedon';
    public $defaultSortDirection = 'DESC';
    public $objectType = 'gpm.package';

    public function prepareRow(xPDOObject $object)
    {
        $ta = $object->toArray();
        $ta['updatedon'] = ($ta['updatedon']) ? date('Y-m-d H:i:s', $ta['updatedon']) : '';
        return $ta;
    }

    public function prepareQueryBeforeCount(xPDOQuery $c)
    {
        $c = parent::prepareQueryBeforeCount($c);

        $query = $this->getProperty('query');
        if (!empty($query)) {
            $c->where([
                'name:LIKE' => "%{$query}%",
                'OR:description:LIKE' => "%{$query}%",
                'OR:author:LIKE' => "%{$query}%",
                'OR:version:LIKE' => "%{$query}%",
            ]);
        }

        return $c;
    }
}
