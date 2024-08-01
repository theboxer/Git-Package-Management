<?php

namespace GPM\Operations\Scripts;


use GPM\Config\Config;
use GPM\Config\Validator;
use GPM\Model\GitPackage;
use GPM\Operations\Operation;
use Symfony\Component\Yaml\Yaml;

class Create extends Operation
{
    /** @var \Smarty */
    protected $smarty;

    public function execute(GitPackage $package, string $name): void
    {
        $this->loadSmarty();

        $config = Config::wakeMe($package->config, $this->modx);

        $name = $name . '.gpm.php';

        $file = $config->paths->build . 'scripts' . DIRECTORY_SEPARATOR . $name;

        if (file_exists($file)) {
            $this->logger->error("Script $name already exists ($file)");
            return;
        }

        $content = $this->smarty->fetch('script.php.tpl');
        if (!is_dir($config->paths->build . 'scripts')) {
            mkdir($config->paths->build . 'scripts');
        }

        file_put_contents($file, $content);

        $this->logger->warning('Script created: ' . $name);
        $this->logger->warning('Don\'t forget to update GPM\'s config and add it to either build/scriptsBefore or build/scriptsAfter.');
    }

    protected function loadSmarty(): void
    {
        /** @var \GPM\GPM $gpm */
        $gpm = $this->modx->services->get('gpm');
        $this->smarty = new \Smarty();
        $this->smarty->setCaching(\Smarty::CACHING_OFF);
        $this->smarty->setCompileDir(dirname(__DIR__, 6) . '/cache/compiled_templates/');
        $this->smarty->setTemplateDir($gpm->getOption('templatesPath') . '/scripts/');
    }
}
