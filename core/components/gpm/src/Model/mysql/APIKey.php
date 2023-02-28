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
        'indexes' => 
        array (
            'key' => 
            array (
                'alias' => 'key',
                'primary' => false,
                'unique' => true,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'key' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
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
