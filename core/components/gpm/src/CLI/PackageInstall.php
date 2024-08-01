<?php
namespace GPM\CLI;

use GPM\Model\GitPackage;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PackageInstall extends Command {
    protected static $defaultName = 'package:install';

    /** @var \GPM\Operations\Install */
    private $install;

    public function __construct(\GPM\Operations\Install $install)
    {
        $this->install = $install;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Install new Package')
            ->setHelp('Installs new package from given directory to MODX Revolution')
            ->addArgument('dir', InputArgument::REQUIRED, 'Directory name where the new package is located')
            ->addOption('skipScripts', null, InputOption::VALUE_NONE, 'Skip all scripts')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = new ConsoleLogger($output);
        $this->install->setLogger($logger);

        $dir = $input->getArgument('dir');
        $skipScripts = $input->getOption('skipScripts');

        $this->install->execute($dir, $skipScripts);

        return Command::SUCCESS;
    }
}
