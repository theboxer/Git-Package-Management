<?php
namespace GPM\Model\mysql;

use xPDO\xPDO;

class APIKey extends \GPM\Model\APIKey
{

    public static $metaMap = array (
        'package' => 'GPM\\Model\\',
        'version' => '3.0',
        'table' => 'gpm_api_keys',
        'tableMeta' => 
        array (
            'engine' => 'InnoDB',
        ),
        'fields' => 
        array (
            'package' => NULL,
            'key' => NULL,
            'permissions' => '{}',
            'createdon' => NULL,
        ),
        'fieldMeta' => 
        array (
            'package' => 
            array (
                'dbtype' => 'int',
                'attributes' => 'unsigned',
                'precision' => '11',
                'phptype' => 'integer',
                'null' => false,
            ),
            'key' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '64',
                'phptype' => 'string',
                'null' => false,
                'index' => 'unique',
            ),
            'permissions' => 
            array (
                'dbtype' => 'text',
                'phptype' => 'json',
                'null' => false,
                'default' => '{}',
            ),
            'createdon' => 
            array (
                'dbtype' => 'datetime',
                'phptype' => 'datetime',
                'null' => true,
            ),
        ),
        'aggregates' => 
        array (
            'Package' => 
            array (
                'class' => 'GPM\\Model\\GitPackage',
                'local' => 'package',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
        ),
    );

}
