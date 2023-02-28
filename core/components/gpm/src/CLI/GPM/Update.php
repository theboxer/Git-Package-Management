<?php
namespace GPM\CLI\GPM;

use GPM\CLI\Command;
use GPM\CLI\ConsoleLogger;
use GPM\Model\GitPackage;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Update extends Command {
    private $update;
    private $package;

    public function __construct($name, GitPackage $package, \GPM\Operations\GPM\Update $update)
    {
        $this->update = $update;
        $this->package = $package;

        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Updates GPM')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = new ConsoleLogger($output, [], []);
        $this->update->setLogger($logger);

        $this->update->execute($this->package);

        return Command::SUCCESS;
    }
}
