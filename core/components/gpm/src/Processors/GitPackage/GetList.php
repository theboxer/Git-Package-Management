<?php
namespace GPM\Processors\GitPackage;

use GPM\Model\GitPackage;
use MODX\Revolution\Processors\Model\GetListProcessor;
use xPDO\Om\xPDOObject;

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
        $ta['updatedon'] = date('Y-m-d H:i:s', $ta['updatedon']);
        return $ta;
    }
}
