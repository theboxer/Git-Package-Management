<?php
namespace GPM\CLI;

use GPM\Model\GitPackage;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class KeyRemove extends Command {
    private $operation;
    private $package;

    public function __construct($name, GitPackage $package, \GPM\Operations\Key\Remove $operation)
    {
        $this->operation = $operation;
        $this->package = $package;

        parent::__construct($name);
    }


    protected function configure(): void
    {
        $this
            ->setDescription('Removes existing API Key')
            ->addArgument('key', InputArgument::REQUIRED, 'Key to remove');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = new ConsoleLogger($output);
        $this->operation->setLogger($logger);

        $key = $input->getArgument('key');

        $this->operation->execute($this->package, $key);

        return Command::SUCCESS;
    }
}
