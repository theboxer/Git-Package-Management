<?php
/**
 * Remove an Item.
 * 
 * @package gitpackagemanagement
 * @subpackage processors
 */
class GitPackageManagementRemoveProcessor extends modObjectRemoveProcessor {
    public $classKey = 'GitPackage';
    public $languageTopics = array('gitpackagemanagement:default');
    public $objectType = 'gitpackagemanagement.package';
}
return 'GitPackageManagementRemoveProcessor';