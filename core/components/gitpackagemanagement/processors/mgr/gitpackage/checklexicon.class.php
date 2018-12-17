<?php
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/model/gitpackagemanagement/gpc/gitpackageconfig.class.php';
/**
 * Check lexicon in git repository and collect missing/superfluous entries
 *
 * @package gitpackagemanagement
 * @subpackage processors
 */
class GitPackageManagementCheckLexiconProcessor extends modObjectProcessor {
    /** @var GitPackage $object */
    public $object;
    /** @var GitPackageConfig $config */
    public $config;

    public $packagePath = null;
    public $lexiconPath = null;

    private $language = null;

    private $languageKeys = array();
    private $missingKeys = array();
    private $superfluousKeys = array();

    private $invalidLexicons = array();


    public function prepare(){
        $id = $this->getProperty('id');
        if ($id == null) return $this->failure();

        $this->object = $this->modx->getObject('GitPackage', array('id' => $id));
        if (!$this->object) return $this->failure();

        $this->packagePath = rtrim($this->modx->getOption('gitpackagemanagement.packages_dir', null, null), '/') . '/';
        if ($this->packagePath == null) {
            return $this->modx->lexicon('gitpackagemanagement.package_err_ns_packages_dir');
        }

        $packagePath = $this->packagePath . $this->object->dir_name;

        $configFile = $packagePath . $this->modx->gitpackagemanagement->configPath;
        if (!file_exists($configFile)) {
            return $this->modx->lexicon('gitpackagemanagement.package_err_url_config_nf');
        }

        $config = file_get_contents($configFile);

        $config = $this->modx->fromJSON($config);

        $this->config = new GitPackageConfig($this->modx, $packagePath);
        if ($this->config->parseConfig($config) == false) {
            return $this->modx->lexicon('gitpackagemanagement.package_err_url_config_nf');
        }

        $this->language = $this->modx->getOption('gitpackagemanagement.default_lexicon', null, 'en');

        return true;
    }

    public function process() {
        $prepare = $this->prepare();
        if ($prepare !== true) {
            return $prepare;
        }

        $this->setPaths();

        $this->addKeys();

        $lexiconEntries = $this->loadLexicons();

        $this->missingKeys = array_diff($this->languageKeys, array_keys($lexiconEntries));
        $usedKeys = array_intersect($this->languageKeys, array_keys($lexiconEntries));
        $this->superfluousKeys = array_diff(array_keys($lexiconEntries), $usedKeys);

        $msg = array();
        if ($result = $this->writeKeys('missing')) {
            $msg[] = $result;
        }
        if ($result = $this->writeKeys('superfluous')) {
            $msg[] = $result;
        }
        if (empty($msg)) {
            $msg = 'Every lexicon entry is available!';
        } else {
            $msg = implode("<br><br>", $msg);
        }

        return $this->success($msg);
    }


    private function setPaths() {
        $packagesPath = rtrim($this->modx->getOption('gitpackagemanagement.packages_dir', null, null), '/') . '/';

        $this->packagePath = $packagesPath . $this->object->dir_name . "/";
        $this->packagePath = str_replace('\\', '/', $this->packagePath);

        $this->lexiconPath = $this->packagePath . 'core/components/' . $this->config->getLowCaseName() . '/lexicon/';
    }

    /**
     * Load package lexicons
     *
     * @return array
     */
    private function loadLexicons() {
        $iterator = new \DirectoryIterator($this->lexiconPath . $this->language . '/');
        $_lang = array();
        foreach ($iterator as $path => $current) {
            if (strpos($current->getFilename(), 'inc.php') !== false) {
                try {
                    include $current->getRealPath();
                } catch (Exception $e) {
                    $this->invalidLexicons[] = $current->getFilename();
                }
            }
        }
        return $_lang;
    }

    /**
     * Add used lexicon keys
     */
    private function addKeys() {
        $directory = new \RecursiveDirectoryIterator($this->packagePath, \RecursiveDirectoryIterator::SKIP_DOTS);
        $filter = new \RecursiveCallbackFilterIterator($directory, function ($current, $key, $iterator) {
            /** @var \RecursiveDirectoryIterator $current */
            if ($current->getFilename()[0] === '.') {
                return false;
            }
            if ($current->isDir()) {
                return ($current->getFilename() !== '_build' && $current->getFilename() !== '_packages' && $current->getFilename() !== 'node_modules' && $current->getFilename() !== 'vendor');
            } else {
                $pathinfo = pathinfo($current->getFilename());
                return ($current->isFile() && ($pathinfo['extension'] == 'php' || $pathinfo['extension'] == 'js' || $pathinfo['extension'] == 'html' || $pathinfo['basename'] == 'config.json') && strpos($pathinfo['basename'], 'min.js') == false) ? true : false;
            }
        });
        $iterator = new \RecursiveIteratorIterator($filter);

        foreach ($iterator as $path => $current) {
            $this->addPhpKeys($path);
            $this->addJsKeys($path);
            $this->addChunkKeys($path);
        }
        $this->addSettingKeys();
        $this->addMenuKeys();
        $this->addSnippetKeys();

        $this->languageKeys = array_unique($this->languageKeys);
        sort($this->languageKeys);
    }

    /**
     * Add lexicon calls in php files: modx->lexicon('packageprefix.whatever'
     *
     * @param string $filename
     */
    private function addPhpKeys($filename) {
        $fileContent = file_get_contents($filename);
        $results = array();
        preg_match_all('/modx->lexicon\((["\'])(' . $this->config->getLowCaseName() . '\..*?)\1\s*[,\)]/m', $fileContent, $results);
        if (is_array($results[2])) {
            foreach ($results[2] as $result) {
                // Don't add lexicon keys that ends with a dot or an underscore or that contain a variable
                if (substr($result, -1) !== '.' &&
                    substr($result, -1) !== '_' &&
                    strpos($result, '$') === false
                ) {
                    $this->languageKeys[] = $result;
                }
            }
        }
    }

    /**
     * Add lexicon calls in javascript files: _('packageprefix.whatever'
     *
     * @param string $filename
     */
    private function addJsKeys($filename) {
        $fileContent = file_get_contents($filename);
        $results = array();
        preg_match_all('/_\(([\'"])(' . $this->config->getLowCaseName() . '.*?)\1\s*[,\)]/m', $fileContent, $results);
        if (is_array($results[2])) {
            foreach ($results[2] as $result) {
                // Don't add lexicon keys that ends with a dot or an underscore or that key is concatenated
                if (substr($result, -1) !== '.' &&
                    substr($result, -1) !== '_' &&
                    strpos($result, '+') === false
                ) {
                    $this->languageKeys[] = $result;
                }
            }
        }
    }

    /**
     * Add lexicon calls in chunk files: [[%packageprefix.whatever
     *
     * @param string $filename
     */
    private function addChunkKeys($filename) {
        $fileContent = file_get_contents($filename);
        $results = array();
        $test = '/\[\[%(' . $this->config->getLowCaseName() . '.*?)(\?|\])/m';
        preg_match_all('/\[\[%(' . $this->config->getLowCaseName() . '.*?)[?\]]/m', $fileContent, $results);
        if (is_array($results[1])) {
            foreach ($results[1] as $result) {
                // Don't add lexicon keys that ends with a dot or an underscore or that key contains a setting tag
                if (substr($result, -1) !== '.' &&
                    substr($result, -1) !== '_' &&
                    strpos($result, '[[+') === false
                ) {
                    $this->languageKeys[] = $result;
                }
            }
        }
    }

    /**
     * Add setting language keys
     */
    private function addSettingKeys() {
        $settings = $this->config->getSettings();

        foreach ($settings as $setting) {
            $this->languageKeys[] = 'setting_' . $setting->getNamespacedKey();
            $this->languageKeys[] = 'setting_' . $setting->getNamespacedKey() . '_desc';
            if (!in_array($setting->getArea(), array(
                'authentication', 'caching', 'file', 'furls', 'gateway',
                'language', 'manager', 'session', 'site', 'system'
            ))) {
                $this->languageKeys[] = 'area_' . $setting->getArea();
            }
        }
    }

    /**
     * Add menu language keys
     */
    private function addMenuKeys() {
        $menus = $this->config->getMenus();

        foreach ($menus as $menu) {
            $this->languageKeys[] = $menu->getText();
            $this->languageKeys[] = $menu->getDescription();
        }
    }

    /**
     * Add snippet property language keys
     */
    private function addSnippetKeys() {
        $snippets = $this->config->getElements('snippets');

        foreach ($snippets as $snippet) {
            $properties = $snippet->getProperties();
            foreach ($properties as $property) {
                $this->languageKeys[] = $property['desc'];
            }
        }
    }

    /**
     * Write missing/superfluous keys to the file _missing.php/_superfluous.php in the language folder
     *
     * @param string $type
     * @return bool|string
     */
    private function writeKeys($type) {
        switch ($type) {
            case 'superfluous':
                $keys = &$this->superfluousKeys;
                $keysFile = '_superfluous.php';
                break;
            default:
                $type = 'missing';
                $keys = &$this->missingKeys;
                $keysFile = '_missing.php';
                break;
        }
        if (!empty($keys)) {
            $handle = fopen($this->lexiconPath . $this->language . '/'. $keysFile, 'w');
            if ($handle) {
                fwrite($handle, "<?php\n");
                foreach ($keys as $key) {
                    fwrite($handle, "\$_lang['{$key}'] = '';\n");
                }
                fclose($handle);
            } else {
                return 'Cannot write to file:  ' . $keysFile;
            }

            return '<strong>The ' . $type . ' keys:</strong> ' . implode(', ', array_values($keys)) . '<br><br>' .
                'They could be found in the file <strong>' . $keysFile . '</strong> in the <strong>' . $this->language . '</strong> lexicon.';
        } else {
            if (file_exists($this->lexiconPath . $this->language . '/'. $keysFile)) {
                unlink($this->lexiconPath . $this->language . '/'. $keysFile);
            }
            return false;
        }
    }
}
return 'GitPackageManagementCheckLexiconProcessor';
