<?php
namespace GPM\CLI;

use GPM\Model\GitPackage;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Update extends Command {
    private $update;
    private $package;

    public function __construct($name, GitPackage $package, \GPM\Operations\Update $update)
    {
        $this->update = $update;
        $this->package = $package;

        parent::__construct($name);
    }


    protected function configure(): void
    {
        $this
            ->setDescription('Updates package')
            ->addOption('alterDB', null, InputOption::VALUE_NONE, 'Alters current DB tables')
            ->addOption('recreateDB', null, InputOption::VALUE_NONE, 'Removes all current DB tables and create them from scratch')
            ->addOption('skipMigrations', null, InputOption::VALUE_NONE, 'Skip all migrations')
            ->addOption('skipScripts', null, InputOption::VALUE_NONE, 'Skip all scripts')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = new ConsoleLogger($output, [], []);
        $this->update->setLogger($logger);

        $alterDB = $input->getOption('alterDB');
        $recreateDB = $input->getOption('recreateDB');
        $skipMigrations = $input->getOption('skipMigrations');
        $skipScripts = $input->getOption('skipScripts');

        $this->update->execute($this->package, $recreateDB, $alterDB, $skipMigrations, $skipScripts);

        return Command::SUCCESS;
    }
}
