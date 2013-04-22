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

        $this->addCss($this->gitpackagemanagement->config['cssUrl'].'mgr.css');
        $this->addJavascript($this->gitpackagemanagement->config['jsUrl'].'mgr/gitpackagemanagement.js');
        $this->addHtml('<script type="text/javascript">
        Ext.onReady(function() {
            GitPackageManagement.config = '.$this->modx->toJSON($this->gitpackagemanagement->config).';
            GitPackageManagement.config.connector_url = "'.$this->gitpackagemanagement->config['connectorUrl'].'";
        });
        </script>');
        return parent::initialize();
    }
    public function getLanguageTopics() {
        return array('gitpackagemanagement:default');
    }
    public function checkPermissions() { return true;}
}