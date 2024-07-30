<?php

namespace GPM\Operations\Migrations;


use GPM\Config\Config;
use GPM\Config\Validator;
use GPM\Model\GitPackage;
use GPM\Operations\Operation;
use Symfony\Component\Yaml\Yaml;

class Create extends Operation
{
    /** @var \Smarty */
    protected $smarty;

    public function execute(GitPackage $package, string $version): void
    {
        $this->loadSmarty();

        $config = Config::wakeMe($package->config, $this->modx);

        $versionPacked = $this->packVersion($version);
        $file = $config->paths->build . 'migrations' . DIRECTORY_SEPARATOR . $versionPacked . '.migration.php';

        if (file_exists($file)) {
            $this->logger->error("Migration file for version $version already exists ($file)");
            return;
        }

        $this->smarty->assign('versionPackaged', $versionPacked);
        $this->smarty->assign('version', $version);

        $content = $this->smarty->fetch('migration.php.tpl');
        if (!is_dir($config->paths->build . 'migrations')) {
            mkdir($config->paths->build . 'migrations');
        }

        file_put_contents($file, $content);

        $this->logger->warning('Migration file created: ' . $file);
    }

    private function packVersion($version)
    {
        return preg_replace('/[^a-zA-Z1-9]/', '', $version);
    }

    protected function loadSmarty(): void
    {
        /** @var \GPM\GPM $gpm */
        $gpm = $this->modx->services->get('gpm');
        $this->smarty = new \Smarty();
        $this->smarty->setCaching(\Smarty::CACHING_OFF);
        $this->smarty->setCompileDir(dirname(__DIR__, 6) . '/cache/compiled_templates/');
        $this->smarty->setTemplateDir($gpm->getOption('templatesPath') . '/migrations/');
    }
}
