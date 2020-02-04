<?php

use GitPackageManagement\GitPackageManagement;

abstract class GitPackageManagementBaseManagerController extends \MODX\Revolution\modExtraManagerController {
    /** @var GitPackageManagement $gitpackagemanagement */
    public $gitpackagemanagement;

    public function initialize() {
        $this->gitpackagemanagement =  $this->modx->services->get('gitpackagemanagement');

        $this->addCss($this->gitpackagemanagement->getOption('cssUrl') . 'mgr.css');
        $this->addJavascript($this->gitpackagemanagement->getOption('jsUrl') . 'mgr/gitpackagemanagement.js');
        $this->addHtml('<script type="text/javascript">
        Ext.onReady(function() {
            GitPackageManagement.config = '.$this->modx->toJSON($this->gitpackagemanagement->options).';
        });
        </script>');

        parent::initialize();
    }
    public function getLanguageTopics() {
        return array('gitpackagemanagement:default');
    }
    public function checkPermissions() { return true;}
}
