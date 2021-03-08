<?php
namespace GPM\CLI;

use GPM\Model\GitPackage;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GPMInstall extends Command {
    protected static $defaultName = 'gpm:install';

    protected function configure(): void
    {
        $this
            ->setDescription('Install GPM')
            ->setHelp('Installs GPM to the MDOX Revolution')
            ->addOption('dir', null, InputOption::VALUE_REQUIRED, 'Directory name where GPM is located', basename(dirname(dirname(dirname(dirname(dirname(__DIR__)))))))
            ->addOption('corePath', null, InputOption::VALUE_REQUIRED, 'Absolute path to MODX core directory', dirname(dirname(dirname(dirname(dirname(dirname(__DIR__)))))) . '/modx/core')
            ->addOption('configKey', null, InputOption::VALUE_REQUIRED, 'MODX config key', 'config')
            ->addOption('packagesDir', null, InputOption::VALUE_REQUIRED, 'Absolute path to directory where packages are located', dirname(dirname(dirname(dirname(dirname(dirname(__DIR__)))))))
            ->addOption('packagesBaseUrl', null, InputOption::VALUE_REQUIRED, 'Base URL of packages directory', '/')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $corePath = $input->getOption('corePath');
        if (!is_dir($corePath)) {
            $this->error($output, 'corePath doesn\'t exist; try running "gpm:install -h" for more options');
            return Command::FAILURE;
        }

        $corePath = rtrim($corePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        $configKey = $input->getOption('configKey');
        if (empty($configKey)) {
            $this->error($output, 'configKey is required; try running "gpm:install -h" for more options');
            return Command::FAILURE;
        }

        $modxConfig = $corePath . 'config' . DIRECTORY_SEPARATOR . $configKey .'.inc.php';
        if (!file_exists($modxConfig)) {
            $this->error($output, 'modx config doesn\'t exist; try running "gpm:install -h" for more options');
            return Command::FAILURE;
        }

        $packagesDir = $input->getOption('packagesDir');
        if (empty($packagesDir)) {
            $this->error($output, 'packagesDir is required; try running "gpm:install -h" for more options');
            return Command::FAILURE;
        }

        if (!is_dir($packagesDir)) {
            $this->error($output, 'packagesDir doesn\'t exist; try running "gpm:install -h" for more options');
            return Command::FAILURE;
        }

        $packagesDir = rtrim($packagesDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        $dir = $input->getOption('dir');
        if (empty($dir)) {
            $this->error($output, 'dir is required; try running "gpm:install -h" for more options');
            return Command::FAILURE;
        }

        if (!is_dir($packagesDir . $dir)) {
            $this->error($output, 'dir doesn\'t exist; try running "gpm:install -h" for more options');
            return Command::FAILURE;
        }

        $packagesBaseUrl = $input->getOption('packagesBaseUrl');
        if (empty($packagesBaseUrl)) $packagesBaseUrl = '/';

        /** @var $modx \MODX\Revolution\modX */
        require_once $corePath . 'config/' . $configKey . '.inc.php';
        require_once MODX_CONNECTORS_PATH . 'index.php';

        if (!is_object($modx) || !($modx instanceof \MODX\Revolution\modX)) {
            $this->error($output, 'failed to load MODX; try running "gpm:install -h" for more options');
            return Command::FAILURE;
        }

        $logger = new ConsoleLogger($output);

        $install = new \GPM\Operations\GPM\Install($modx, $logger);

        $install->execute($dir, $packagesDir, $packagesBaseUrl);

        return Command::SUCCESS;
    }
}
