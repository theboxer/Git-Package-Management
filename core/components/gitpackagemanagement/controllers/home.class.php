<?php
/**
 * Loads the home page.
 *
 * @package gitpackagemanagement
 * @subpackage controllers
 */
class GitPackageManagementHomeManagerController extends GitPackageManagementBaseManagerController {
    public function process(array $scriptProperties = array()) {

    }
    public function getPageTitle() { return $this->modx->lexicon('gitpackagemanagement'); }
    public function loadCustomCssJs() {
        $this->addJavascript($this->gitpackagemanagement->config['jsUrl'].'mgr/widgets/packages.window.js');
        $this->addJavascript($this->gitpackagemanagement->config['jsUrl'].'mgr/widgets/packages.grid.js');
        $this->addJavascript($this->gitpackagemanagement->config['jsUrl'].'mgr/widgets/home.panel.js');
        $this->addLastJavascript($this->gitpackagemanagement->config['jsUrl'].'mgr/sections/home.js');
    }
    public function getTemplateFile() { return $this->gitpackagemanagement->config['templatesPath'].'home.tpl'; }
}