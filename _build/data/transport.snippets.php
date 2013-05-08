<?php
/**
 * Add snippets to build
 * 
 * @package gitpackagemanagement
 * @subpackage build
 */
$snippets = array();

$snippets[0]= $modx->newObject('modSnippet');
$snippets[0]->fromArray(array(
    'id' => 0,
    'name' => 'GitPackageManagement',
    'description' => 'Displays Items.',
    'snippet' => getSnippetContent($sources['source_core'].'/elements/snippets/snippet.gitpackagemanagement.php'),
),'',true,true);
$properties = include $sources['build'].'properties/properties.gitpackagemanagement.php';
$snippets[0]->setProperties($properties);
unset($properties);

return $snippets;