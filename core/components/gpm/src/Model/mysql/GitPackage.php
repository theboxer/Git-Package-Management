<?php
namespace GPM\Model\mysql;

use xPDO\xPDO;

class GitPackage extends \GPM\Model\GitPackage
{

    public static $metaMap = array (
        'package' => 'GPM\\Model\\',
        'version' => '3.0',
        'table' => 'gpm_packages',
        'tableMeta' => 
        array (
            'engine' => 'InnoDB',
        ),
        'fields' => 
        array (
            'name' => NULL,
            'description' => '',
            'version' => '',
            'author' => '',
            'dir_name' => NULL,
            'config' => '',
            'updatedon' => NULL,
        ),
        'fieldMeta' => 
        array (
            'name' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '100',
                'phptype' => 'string',
                'null' => false,
            ),
            'description' => 
            array (
                'dbtype' => 'text',
                'phptype' => 'string',
                'null' => false,
                'default' => '',
            ),
            'version' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '32',
                'phptype' => 'string',
                'null' => false,
                'default' => '',
            ),
            'author' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '32',
                'phptype' => 'string',
                'null' => false,
                'default' => '',
            ),
            'dir_name' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '100',
                'phptype' => 'string',
                'null' => false,
            ),
            'config' => 
            array (
                'dbtype' => 'mediumtext',
                'phptype' => 'string',
                'null' => false,
                'default' => '',
            ),
            'updatedon' => 
            array (
                'dbtype' => 'int',
                'precision' => '10',
                'phptype' => 'integer',
                'null' => true,
            ),
        ),
    );

}
