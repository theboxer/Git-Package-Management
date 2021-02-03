<?php
namespace GPM\CLI;

use GPM\Model\GitPackage;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Build extends Command {
    private $build;
    private $package;

    public function __construct($name, GitPackage $package, \GPM\Operations\Build $build)
    {
        $this->build = $build;
        $this->package = $package;

        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Build transport package');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = new ConsoleLogger($output);
        $this->build->setLogger($logger);

        $this->build->execute($this->package->dir_name);

        return Command::SUCCESS;
    }
}
