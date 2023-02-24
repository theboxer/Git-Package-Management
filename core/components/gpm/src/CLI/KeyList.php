<?php
namespace GPM\CLI;

use GPM\Model\GitPackage;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class KeyList extends Command {
    private $op;
    private $package;

    public function __construct($name, GitPackage $package, \GPM\Operations\Key\ListKeys $op)
    {
        $this->op = $op;
        $this->package = $package;

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
        $this->op->setLogger($logger);

        $this->op->execute($this->package);

        return Command::SUCCESS;
    }
}
