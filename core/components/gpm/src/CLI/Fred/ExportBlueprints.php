<?php
namespace GPM\CLI\Fred;

use GPM\CLI\Command;
use GPM\CLI\ConsoleLogger;
use GPM\Model\GitPackage;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportBlueprints extends Command {

    private $export;
    private $package;

    public function __construct($name, GitPackage $package, \GPM\Operations\Fred\ExportBlueprints $export)
    {
        $this->export = $export;
        $this->package = $package;

        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Exports Fred\'s blueprints')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = new ConsoleLogger($output);
        $this->export->setLogger($logger);

        $this->export->execute($this->package);

        return Command::SUCCESS;
    }
}
