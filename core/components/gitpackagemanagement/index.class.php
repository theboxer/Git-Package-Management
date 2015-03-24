<?php
require_once dirname(__FILE__) . '/model/gitpackagemanagement/gitpackagemanagement.class.php';
/**
 * @package gitpackagemanagement
 */
class IndexManagerController extends GitPackageManagementBaseManagerController {
    public static function getDefaultController() { return 'home'; }
}

abstract class GitPackageManagementBaseManagerController extends modExtraManagerController {
    /** @var GitPackageManagement $gitpackagemanagement */
    public $gitpackagemanagement;

    public function initialize() {
        $this->gitpackagemanagement = new GitPackageManagement($this->modx);

        $this->addCss($this->gitpackagemanagement->getOption('cssUrl') . 'mgr.css');
        $this->addJavascript($this->gitpackagemanagement->getOption('jsUrl') . 'mgr/gitpackagemanagement.js');
        $this->addHtml('<script type="text/javascript">
        Ext.onReady(function() {
            GitPackageManagement.config = '.$this->modx->toJSON($this->gitpackagemanagement->options).';
            GitPackageManagement.config.connector_url = "' . $this->gitpackagemanagement->getOption('connectorUrl') . '";
        });
        </script>');
        return parent::initialize();
    }
    public function getLanguageTopics() {
        return array('gitpackagemanagement:default');
    }
    public function checkPermissions() { return true;}
}