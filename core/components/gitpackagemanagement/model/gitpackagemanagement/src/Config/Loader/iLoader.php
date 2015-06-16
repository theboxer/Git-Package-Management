<?php
namespace GPM\Config\Loader;

interface iLoader
{
    /**
     * Load general info about packge
     * 
     * @return array
     */
    public function loadGeneral();

    /**
     * Load Actions
     * 
     * @return array
     */
    public function loadActions();

    /**
     * Load Menus
     * 
     * @return array
     */
    public function loadMenus();

    /**
     * Load Categories
     *
     * @return array
     */
    public function loadCategories();
        
    /**
     * Load Plugins
     * 
     * @return array
     */
    public function loadPlugins();

    /**
     * Load Snippets
     * 
     * @return array
     */
    public function loadSnippets();

    /**
     * Load Chunks
     * 
     * @return array
     */
    public function loadChunks();

    /**
     * Load Templates
     * 
     * @return array
     */
    public function loadTemplates();

    /**
     * Load TVs
     * 
     * @return array
     */
    public function loadTVs();

    /**
     * Load Resources
     * 
     * @return array
     */
    public function loadResources();

    /**
     * Load System settings
     * 
     * @return array
     */
    public function loadSystemSettings();

    /**
     * Load Database
     * 
     * @return array
     */
    public function loadDatabase();

    /**
     * Load Extension package
     * 
     * @return array
     */
    public function loadExtensionPackage();

    /**
     * Load Build
     * 
     * @return array
     */
    public function loadBuild();
    
    /**
     * Load Dependencies
     * 
     * @return array
     */
    public function loadDependencies();
}