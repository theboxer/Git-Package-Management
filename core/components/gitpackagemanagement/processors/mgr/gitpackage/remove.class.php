<?php
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/model/gitpackagemanagement/gitpackageconfig.class.php';
/**
 * Remove and uninstall package
 * 
 * @package gitpackagemanagement
 * @subpackage processors
 */
class GitPackageManagementRemoveProcessor extends modObjectRemoveProcessor {
    public $classKey = 'GitPackage';
    public $languageTopics = array('gitpackagemanagement:default');
    public $objectType = 'gitpackagemanagement.package';
    /** @var GitPackage $object */
    public $object;
    /** @var GitPackageConfig $config */
    private $config;

    public function beforeRemove() {
        /**
         * Check if is set packages dir in MODx system settings
         */
        $packagePath = $this->modx->getOption('gitpackagemanagement.packages_dir',null,null);
        if($packagePath == null){
            return $this->modx->lexicon('gitpackagemanagement.package_err_ns_packages_dir');
        }

        $configRet = $this->setConfig($packagePath . $this->object->dir_name);
        if($configRet !== true){
            return $configRet;
        }

        $this->uninstallPackage();

        return parent::beforeRemove();
    }

    private function setConfig($packagePath){
        $configFile = $packagePath . $this->modx->gitpackagemanagement->configPath;
        if(!file_exists($configFile)){
            return $this->modx->lexicon('gitpackagemanagement.package_err_url_config_nfif');
        }

        $this->config = new GitPackageConfig($this->modx, $packagePath);

        if($this->config->parseConfig($this->modx->fromJSON(file_get_contents($configFile))) == false) {
            return $this->modx->lexicon('gitpackagemanagement.package_err_url_config_nf');
        }

        return true;
    }

    private function uninstallPackage() {
        $this->removeElements();
        $this->removeTables();
        $this->removeExtensionPackage();
        $this->removeNamespace();

    }

    private function removeNamespace() {
        /** @var modNamespace $ns */
        $ns = $this->modx->getObject('modNamespace', array('name' => $this->config->getLowCaseName()));
        if($ns){
            $ns->remove();
        }
    }

    private function removeExtensionPackage() {
        $extPackage = $this->config->getExtensionPackage();
        if($extPackage != false){
            $this->modx->removeExtensionPackage($this->config->getLowCaseName());
        }
    }

    private function removeElements() {
        $this->removePlugins();
        $this->removeSnippets();
        $this->removeChunks();
        $this->removeTemplates();
        $this->removeCategory();
    }

    private function removeTables() {
        if($this->config->getDatabase() != null){
            $modelPath = $this->modx->getOption($this->config->getLowCaseName().'.core_path',null,$this->modx->getOption('core_path').'components/'.$this->config->getLowCaseName().'/').'model/';
            $this->modx->addPackage($this->config->getLowCaseName(), $modelPath, $this->config->getDatabase()->getPrefix());

            $manager = $this->modx->getManager();

            foreach($this->config->getDatabase()->getTables() as $table){
                $manager->removeObjectContainer($table);
            }
        }
    }

    private function removePlugins() {
        $plugins = $this->config->getElements('plugins');
        if(count($plugins) > 0){
            /** @var GitPackageConfigElementPlugin $plugin */
            foreach($plugins as $plugin){
                /** @var modPlugin $pluginObject */
                $pluginObject = $this->modx->getObject('modPlugin', array('name' => $plugin->getName()));
                if($pluginObject) $pluginObject->remove();
            }
        }
    }

    private function removeSnippets() {
        $snippets = $this->config->getElements('snippets');
        if(count($snippets) > 0){
            /** @var GitPackageConfigElementSnippet $snippet */
            foreach($snippets as $snippet){
                /** @var modSnippet $snippetObject */
                $snippetObject = $this->modx->getObject('modSnippet', array('name' => $snippet->getName()));
                if($snippetObject) $snippetObject->remove();
            }
        }
    }

    private function removeChunks() {
        $chunks = $this->config->getElements('chunks');
        if(count($chunks) > 0){
            /** @var GitPackageConfigElementChunk $chunk */
            foreach($chunks as $chunk){
                /** @var modChunk $chunkObject */
                $chunkObject = $this->modx->getObject('modChunk', array('name' => $chunk->getName()));
                if($chunkObject) $chunkObject->remove();
            }
        }
    }

    private function removeTemplates() {
        $templates = $this->config->getElements('templates');
        if(count($templates) > 0){
            /** @var GitPackageConfigElementTemplate $template */
            foreach($templates as $template){
                /** @var modChunk $chunkObject */
                $templateObject = $this->modx->getObject('modTemplate', array('templatename' => $template->getName()));
                if($templateObject) $templateObject->remove();
            }
        }
    }

    private function removeCategory() {
        /** @var modCategory $cat */
        $cat = $this->modx->getObject('modCategory', array('category' => $this->config->getLowCaseName()));
        if($cat) $cat->remove();

    }
}
return 'GitPackageManagementRemoveProcessor';