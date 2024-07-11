<?php
namespace GPM\CLI\Fred;

use GPM\CLI\Command;
use GPM\CLI\ConsoleLogger;
use GPM\Model\GitPackage;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Export extends Command {

    private $export;
    private $package;

    public function __construct($name, GitPackage $package, \GPM\Operations\Fred\Export $export)
    {
        $this->export = $export;
        $this->package = $package;

        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Exports Fred\'s blueprints')
            ->addArgument(
                'parts',
                InputArgument::IS_ARRAY,
                'List of Fred objects to export',
                ['blueprints', 'elements', 'optionSets', 'rteConfigs', 'elementCategories', 'blueprintCategories', 'themedTemplates']
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = new ConsoleLogger($output);
        $this->export->setLogger($logger);

        $parts = $input->getArgument('parts');

        $this->export->execute($this->package, $parts);

        return Command::SUCCESS;
    }
}
