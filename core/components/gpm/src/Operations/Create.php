<?php

namespace GPM\Operations;


use GPM\Config\Parts\General;
use Symfony\Component\Yaml\Yaml;

class Create extends Operation
{
    /** @var \Smarty */
    protected $smarty;

    /** @var \MODX\Revolution\modCacheManager */
    protected $cacheManager;

    public function execute(string $dir, array $generalData, bool $withComposer = false, bool $force = false): void
    {
        $this->loadSmarty();

        $general = new General($generalData);
        $valid = $general->validate($this->logger);
        if (!$valid) return;

        $packagesDir = $this->modx->getOption('gpm.packages_dir');
        $this->cacheManager = $this->modx->getCacheManager();

        if ($force) {
            $this->cacheManager->deleteTree($packagesDir . $dir, ['deleteTop' => true, 'extensions' => []]);
        }

        if (is_dir($packagesDir . $dir)) {
            $this->logger->error("Directory {$dir} already exists in {$packagesDir}.");
            return;
        }

        $this->smarty->assign('namespace', $general->namespace);
        $this->smarty->assign('name', $general->name);
        $this->smarty->assign('lowCaseName', $general->lowCaseName);


        $corePath = $packagesDir . $dir . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . $general->lowCaseName;
        $assetsPath = $packagesDir . $dir . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . $general->lowCaseName;

        $this->cacheManager->writeTree($packagesDir . $dir . DIRECTORY_SEPARATOR . '_build');

        $this->createCoreDirs($corePath);
        $this->createAssetsDirs($assetsPath);

        $cmpFiles = [
            'base.js.tpl' => $assetsPath . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'mgr' . DIRECTORY_SEPARATOR . $general->lowCaseName . '.js',
            'page.js.tpl' => $assetsPath . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'mgr' . DIRECTORY_SEPARATOR . 'sections' . DIRECTORY_SEPARATOR . 'manage.js',
            'panel.js.tpl' => $assetsPath . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'mgr' . DIRECTORY_SEPARATOR . 'widgets' . DIRECTORY_SEPARATOR . 'manage.panel.js',
            'empty_1' => $assetsPath . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'mgr.css',
            'lexicons.php.tpl' => $corePath . DIRECTORY_SEPARATOR . 'lexicon' . DIRECTORY_SEPARATOR . 'en' . DIRECTORY_SEPARATOR . 'default.inc.php',
            'template.tpl.tpl' => $corePath . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'manage.tpl',
            'ManageController.php.tpl' => $corePath . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'manage.class.php',
            'BaseController.php.tpl' => $corePath . DIRECTORY_SEPARATOR . 'index.class.php',
            'BaseClass.php.tpl' => $corePath . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . $general->name . '.php',
        ];

        foreach ($cmpFiles as $tpl => $path) {
            $content = '';

            if (substr($tpl, 0, 6) !== 'empty_') {
                $content = $this->smarty->fetch($tpl);
            }

            $this->cacheManager->writeFile($path, $content);
        }

        $schemaPath = $corePath . DIRECTORY_SEPARATOR . 'schema' . DIRECTORY_SEPARATOR . $general->lowCaseName . '.mysql.schema.xml';
        $composerPath = $corePath . DIRECTORY_SEPARATOR . 'composer.json';
        $bootstrapPath = $corePath . DIRECTORY_SEPARATOR . 'bootstrap.php';
        $gpmConfigPath = $packagesDir . $dir . DIRECTORY_SEPARATOR . '_build' . DIRECTORY_SEPARATOR . 'gpm.yaml';

        $this->cacheManager->writeFile($schemaPath, $this->smarty->fetch('schema.xml.tpl'));

        if ($withComposer) {
            $this->cacheManager->writeFile($corePath . DIRECTORY_SEPARATOR . 'bootstrap.php', $this->smarty->fetch('bootstrap_composer.php.tpl'));
            $this->cacheManager->writeFile($composerPath, $this->smarty->fetch('composer.json.tpl'));
        } else {
            $this->cacheManager->writeFile($bootstrapPath, $this->smarty->fetch('bootstrap.php.tpl'));
        }

        $generalData['menus'] = [
            [
                'text' => $general->lowCaseName . '.menu',
                'action' => 'manage',
            ]
        ];

        $yaml = Yaml::dump($generalData, 4);

        $this->cacheManager->writeFile($gpmConfigPath, $yaml);

        $this->logger->warning('Package created in: ' . $packagesDir . $dir);
        $this->logger->warning('');

        if ($withComposer) {
            $this->logger->warning('COMPOSER');
            $this->logger->warning(' - ' . $composerPath . ' adjust as needed.');
            $this->logger->warning(' - Don\'t forget to run "composer install" from ' . $corePath . ' directory.');
            $this->logger->warning('');
        }

        $this->logger->warning('GPM CONFIG');
        $this->logger->warning(' - ' . $gpmConfigPath . ' adjust as needed.');
        $this->logger->warning('');

        $this->logger->warning('DATABASE');
        $this->logger->warning(' - ' . $schemaPath . ' adjust as needed.');
        $this->logger->warning('');

        $this->logger->warning('BOOTSTRAP');
        $this->logger->warning('- ' . $bootstrapPath . ' and adjust as needed.');
        $this->logger->warning('');

        $this->logger->warning('CMP');
        foreach ($cmpFiles as $path) {
            $this->logger->warning('- ' . $path);
        }
        $this->logger->warning('');

        $this->logger->warning('RUN "gpm package:install ' . $dir . '" to install the created package.');

        $this->logger->warning('');
        $this->logger->warning('Thank you for using GPM -- John');
    }

    protected function createCoreDirs($corePath)
    {
        $this->cacheManager->writeTree($corePath . DIRECTORY_SEPARATOR . 'src');
        $this->cacheManager->writeTree($corePath . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Processors');
        $this->cacheManager->writeTree($corePath . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Model');
        $this->cacheManager->writeTree($corePath . DIRECTORY_SEPARATOR . 'schema');
        $this->cacheManager->writeTree($corePath . DIRECTORY_SEPARATOR . 'lexicon' . DIRECTORY_SEPARATOR . 'en');
        $this->cacheManager->writeTree($corePath . DIRECTORY_SEPARATOR . 'docs');
        $this->cacheManager->writeTree($corePath . DIRECTORY_SEPARATOR . 'controllers');
        $this->cacheManager->writeTree($corePath . DIRECTORY_SEPARATOR . 'templates');
    }

    protected function createAssetsDirs($assetsPath)
    {
        $this->cacheManager->writeTree($assetsPath . DIRECTORY_SEPARATOR . 'css');
        $this->cacheManager->writeTree($assetsPath . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'mgr' . DIRECTORY_SEPARATOR . 'sections');
        $this->cacheManager->writeTree($assetsPath . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'mgr' . DIRECTORY_SEPARATOR . 'widgets');
    }

    protected function loadSmarty(): void
    {
        /** @var \GPM\GPM $gpm */
        $gpm = $this->modx->services->get('gpm');
        $this->smarty = new \Smarty();
        $this->smarty->setCaching(\Smarty::CACHING_OFF);
        $this->smarty->setCompileDir(dirname(dirname(dirname(dirname(dirname(__DIR__))))) . '/cache/compiled_templates/');
        $this->smarty->setTemplateDir($gpm->getOption('templatesPath') . '/createPackage/');
    }
}
