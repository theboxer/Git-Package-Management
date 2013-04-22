<?php
$gitpackagemanagement = $modx->getService('gitpackagemanagement','GitPackageManagement',$modx->getOption('gitpackagemanagement.core_path',null,$modx->getOption('core_path').'components/gitpackagemanagement/').'model/gitpackagemanagement/',$scriptProperties);
if (!($gitpackagemanagement instanceof GitPackageManagement)) return '';


$m = $modx->getManager();
$m->createObjectContainer('GitPackage');
return 'Table created.';