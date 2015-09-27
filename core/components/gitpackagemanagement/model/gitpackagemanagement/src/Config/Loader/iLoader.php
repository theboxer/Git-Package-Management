<?php
namespace GPM\Config\Loader;

use GPM\Config\Object\General;
use GPM\Config\Parser\Parser;
use GPM\Config\Validator\ValidatorException;

interface iLoader
{
    /**
     * @param Parser $parser
     * @param $path
     * @param General $general
     */
    public function __construct(Parser $parser);
    
    /**
     * Load general info about package
     *
     * @param bool $skip
     * @return bool
     * @throws ValidatorException
     * @throws \Exception
     */
    public function loadGeneral($skip = true);

    /**
     * Load Actions
     *
     * @param bool $skip
     * @return bool
     * @throws ValidatorException
     * @throws \Exception
     */
    public function loadActions($skip = true);

    /**
     * Load Menus
     *
     * @param bool $skip
     * @return bool
     * @throws ValidatorException
     * @throws \Exception
     */
    public function loadMenus($skip = true);

    /**
     * Load Categories
     *
     * @param bool $skip
     * @return bool
     * @throws ValidatorException
     * @throws \Exception
     */
    public function loadCategories($skip = true);

    /**
     * Load Plugins
     *
     * @param bool $skip
     * @return bool
     * @throws ValidatorException
     * @throws \Exception
     */
    public function loadPlugins($skip = true);

    /**
     * Load Snippets
     *
     * @param bool $skip
     * @return bool
     * @throws ValidatorException
     * @throws \Exception
     */
    public function loadSnippets($skip = true);

    /**
     * Load Chunks
     *
     * @param bool $skip
     * @return bool
     * @throws ValidatorException
     * @throws \Exception
     */
    public function loadChunks($skip = true);

    /**
     * Load Templates
     *
     * @param bool $skip
     * @return bool
     * @throws ValidatorException
     * @throws \Exception
     */
    public function loadTemplates($skip = true);

    /**
     * Load TVs
     *
     * @param bool $skip
     * @return bool
     * @throws ValidatorException
     * @throws \Exception
     */
    public function loadTVs($skip = true);

    /**
     * Load Resources
     *
     * @param bool $skip
     * @return array
     * @throws ValidatorException
     * @throws \Exception
     */
    public function loadResources($skip = true);

    /**
     * Load System settings
     *
     * @param bool $skip
     * @return bool
     * @throws ValidatorException
     * @throws \Exception
     */
    public function loadSystemSettings($skip = true);

    /**
     * Load Database
     *
     * @param bool $skip
     * @return bool
     * @throws ValidatorException
     * @throws \Exception
     */
    public function loadDatabase($skip = true);

    /**
     * Load Extension package
     *
     * @param bool $skip
     * @return bool
     * @throws ValidatorException
     * @throws \Exception
     */
    public function loadExtensionPackage($skip = true);

    /**
     * Load Build
     *
     * @param bool $skip
     * @return bool
     * @throws ValidatorException
     * @throws \Exception
     */
    public function loadBuild($skip = true);

    /**
     * Load Dependencies
     *
     * @param bool $skip
     * @return bool
     * @throws ValidatorException
     * @throws \Exception
     */
    public function loadDependencies($skip = true);
}