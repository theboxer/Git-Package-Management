<?php

require_once __DIR__ .'/../index.class.php';
class GitPackageManagementHomeManagerController extends GitPackageManagementBaseManagerController {
    public function process(array $scriptProperties = array()) {

    }
    public function getPageTitle() {
        return $this->modx->lexicon('gitpackagemanagement');
    }

    public function loadCustomCssJs() {
        $this->addJavascript($this->gitpackagemanagement->getOption('jsUrl') . 'mgr/widgets/packages.window.js');
        $this->addJavascript($this->gitpackagemanagement->getOption('jsUrl') . 'mgr/widgets/packages.grid.js');
        $this->addJavascript($this->gitpackagemanagement->getOption('jsUrl') . 'mgr/widgets/home.panel.js');
        $this->addLastJavascript($this->gitpackagemanagement->getOption('jsUrl') . 'mgr/sections/home.js');
    }

    public function getTemplateFile() {
        return $this->gitpackagemanagement->getOption('templatesPath') . 'home.tpl';
    }
}
