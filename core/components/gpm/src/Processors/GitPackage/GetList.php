<?php
namespace GPM\Processors\GitPackage;

use GPM\Model\GitPackage;
use MODX\Revolution\Processors\Model\GetListProcessor;

class GetList extends GetListProcessor
{
    public $classKey = GitPackage::class;
    public $languageTopics = ['gpm:default'];
    public $defaultSortField = 'updatedon';
    public $defaultSortDirection = 'DESC';
    public $objectType = 'gpm.package';
}
