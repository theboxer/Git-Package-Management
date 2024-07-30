<?php
namespace GPM\CLI\Migrations;

use GPM\CLI\Command;
use GPM\CLI\ConsoleLogger;
use GPM\Config\Parts\General;
use GPM\Model\GitPackage;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class Create extends Command {
    private $package;
    private $operation;

    public function __construct($name, GitPackage $package, \GPM\Operations\Migrations\Create $operation)
    {
        $this->package = $package;
        $this->operation = $operation;

        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Create new migration')
            ->addArgument('version', InputArgument::OPTIONAL, 'Version for the migration. (Defaults to currently installed version - ' . $this->package->version .')')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = new ConsoleLogger($output);
        $this->operation->setLogger($logger);

        $version = $input->getArgument('version');
        if (empty($version)) {
            $version = $this->package->version;
        }

        $this->operation->execute($this->package, $version);

        return Command::SUCCESS;
    }
}
