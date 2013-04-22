<?php
/**
 * @package gitpackagemanagement
 */
$xpdo_meta_map['GitPackage']= array (
  'package' => 'gitpackagemanagement',
  'version' => NULL,
  'table' => 'git_packages',
  'extends' => 'xPDOSimpleObject',
  'fields' => 
  array (
    'name' => NULL,
    'description' => '',
    'version' => '',
    'author' => '',
    'url' => '',
    'dir_name' => NULL,
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
      'phptype' => 'text',
      'null' => false,
      'default' => '',
    ),
    'version' => 
    array (
      'dbtype' => 'text',
      'precision' => '32',
      'phptype' => 'text',
      'null' => false,
      'default' => '',
    ),
    'author' => 
    array (
      'dbtype' => 'text',
      'precision' => '32',
      'phptype' => 'text',
      'null' => false,
      'default' => '',
    ),
    'url' => 
    array (
      'dbtype' => 'text',
      'precision' => '200',
      'phptype' => 'text',
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
  ),
);
