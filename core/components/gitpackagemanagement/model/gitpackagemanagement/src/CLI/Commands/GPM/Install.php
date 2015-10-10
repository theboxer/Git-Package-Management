<?php
namespace GPM\CLI\Commands\GPM;

use GPM\CLI\Commands\Command;
use GPM\Config\Config;
use GPM\Config\Loader\JSON;
use GPM\Config\Parser\Parser;
use GPM\Config\Validator\ValidatorException;
use GPM\Utils;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class Install extends Command
{
    /** @var \modX $modx */
    protected $modx;
    /** @var Config $config */
    protected $config;
    protected $packageCorePath;
    protected $packageAssetsPath;
    protected $packageAssetsUrl;

    protected function configure()
    {
        $this
            ->setName('gpm:install')
            ->setDescription('Install GPM')
            ->addOption(
                'dir',
                null,
                InputOption::VALUE_REQUIRED,
                'Directory name where GPM is located'
            )
            ->addOption(
                'packagesDir',
                null,
                InputOption::VALUE_REQUIRED,
                'Absolute path to directory where packages are located'
            )
            ->addOption(
                'packagesBaseUrl',
                null,
                InputOption::VALUE_REQUIRED,
                'Base URL of packages directory',
                '/'
            )
            ->addOption(
                'noUI',
                null,
                InputOption::VALUE_NONE,
                'Install GPM without UI'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
        $logger = new ConsoleLogger($output);

        $noUI = $input->getOption('noUI');
        
        $dir = $input->getOption('dir');
        if (empty($dir)) {
            $dir = explode('/', Utils::getGPMDir());
            $dir = array_pop($dir);
        }   

        $packagesDir = $input->getOption('packagesDir');
        if (empty($packagesDir)) {
            $logger->error('Option packagesDir is required.');
            return;
        }

        $packagesBaseUrl = $input->getOption('packagesBaseUrl');

        $corePath = Utils::getGPMDir() . '/core/components/gitpackagemanagement/';
        
        /** @var \GitPackageManagement $gpm */
        $gpm = $this->getApplication()->modx->getService(
            'gitpackagemanagement',
            'GitPackageManagement',
            $corePath . 'model/gitpackagemanagement/',
            array(
                'core_path' => $corePath
            )
        );
        $this->getApplication()->setGPM($gpm);
        
        try {
            $config = new Config($this->getApplication()->modx, $dir, dirname(Utils::getGPMDir()) . '/');
            $parser = new Parser($this->getApplication()->modx, $config);
            $loader = new JSON($parser);
            $loader->loadAll();

            unset($config->systemSettings['gitpackagemanagement.assets_path']);
            unset($config->systemSettings['gitpackagemanagement.assets_url']);
            
            $config->systemSettings['gitpackagemanagement.core_path']->value = Utils::getGPMDir() . '/core/components/' . $config->general->lowCaseName . '/';
            $config->systemSettings['gitpackagemanagement.packages_dir']->value = $packagesDir;
            $config->systemSettings['gitpackagemanagement.packages_base_url']->value = $packagesBaseUrl;
            $config->general->assetsPath = '{assets_path}components/' . $config->general->lowCaseName . '/';

            if ($noUI) {
                $config->actions = [];
                $config->menus = [];
            }
            
            $installer = new \GPM\Action\Install($config, $logger);
            $installer->install();
            
            if (!$noUI) {
                $fs = new Filesystem();
                $fs->mirror(Utils::getGPMDir() . '/assets/components/' . $config->general->lowCaseName, $this->getApplication()->modx->getOption('assets_path') . 'components/' . $config->general->lowCaseName);
            }
        } catch (ValidatorException $ve) {
            $logger->error('Config file is invalid.');
            $logger->error($ve->getMessage());

            return null;
        } catch (\Exception $e) {
            $logger->error($e->getMessage());

            return null;
        }
        
        $logger->info('GPM installed.');
    }
}
