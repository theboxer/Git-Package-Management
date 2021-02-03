<?php
namespace GPM\CLI;

use GPM\Model\GitPackage;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Remove extends Command {
    private $remove;
    private $package;

    public function __construct($name, GitPackage $package, \GPM\Operations\Remove $remove)
    {
        $this->remove = $remove;
        $this->package = $package;

        parent::__construct($name);
    }


    protected function configure(): void
    {
        $this
            ->setDescription('Removes the package');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = new ConsoleLogger($output);
        $this->remove->setLogger($logger);

        $this->remove->execute($this->package);

        return Command::SUCCESS;
    }
}
