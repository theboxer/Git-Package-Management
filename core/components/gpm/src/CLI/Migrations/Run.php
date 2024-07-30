<?php

namespace GPM\CLI\Migrations;

use GPM\CLI\Command;
use GPM\CLI\ConsoleLogger;
use GPM\Model\GitPackage;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Run extends Command {
    private $package;
    private $operation;

    public function __construct($name, GitPackage $package, \GPM\Operations\Migrations\Run $operation)
    {
        $this->package = $package;
        $this->operation = $operation;

        parent::__construct($name);
    }


    protected function configure(): void
    {
        $this
            ->setDescription('Lists all API Keys for the package');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = new ConsoleLogger($output);
        $this->operation->setLogger($logger);

        $this->operation->execute($this->package);

        return Command::SUCCESS;
    }
}