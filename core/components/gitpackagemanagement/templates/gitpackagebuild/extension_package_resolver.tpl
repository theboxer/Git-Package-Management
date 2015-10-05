<?php
/**
 * Resolve creating db tables
 *
 * THIS RESOLVER IS AUTOMATICALLY GENERATED, NO CHANGES WILL APPLY
 *
 * @package {{$general->lowCaseName}}
 * @subpackage build
 */

if (!function_exists('not22')) {
    function not22() {
        global $modx;

        $version = $modx->getVersionData();

        $version['version'];
        $version['major_version'];
        
        return (($version['version'] > 2) || (($version['version'] == 2) && ($version['major_version'] > 2)));
    }
}

if ($object->xpdo) {
    /** @var modX $modx */
    $modx =& $object->xpdo;

    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            if (not22() === true) {
                $pkg = $modx->getObject('modExtensionPackage', array('namespace' => '{{$extPackage->namespace}}', 'name' => '{{$extPackage->name}}'));
                if (!$pkg) {
                    $pkg = $modx->newObject('modExtensionPackage');
                    $pkg->set('namespace', '{{$extPackage->namespace}}');
                }

                $pkg->set('name', '{{$extPackage->name}}');
                $pkg->set('path', '[[++core_path]]components/{{$general->lowCaseName}}/model/');
                $pkg->set('table_prefix', '{{$extPackage->tablePrefix}}');
                $pkg->set('service_class', '{{$extPackage->serviceClass}}');
                $pkg->set('service_name', '{{$extPackage->serviceName}}');
                $pkg->save();
            } else {
                $options = [
                    'tablePrefix' => '{{$extPackage->tablePrefix}}'
                ];
                
                {if $extPackage->serviceClass != ''}$options['serviceName'] = '{{$extPackage->serviceName}}';
                $options['serviceClass'] = '{{$extPackage->serviceClass}}';    
                {/if}
                
                $modx->addExtensionPackage('{{$extPackage->name}}', '[[++core_path]]components/{{$general->lowCaseName}}/model/', $options);
            }

            break;
        case xPDOTransport::ACTION_UNINSTALL:
            $modx->removeExtensionPackage('{{$extPackage->name}}');

            if (not22() === true) {
                $modx->removeObject('modExtensionPackage', array('namespace' => '{{$extPackage->namespace}}', 'name' => '{{$extPackage->name}}'));
            }

            break;
    }
}
return true;