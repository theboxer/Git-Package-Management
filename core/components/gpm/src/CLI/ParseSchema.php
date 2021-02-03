<?php
namespace GPM\CLI;

use GPM\Model\GitPackage;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ParseSchema extends Command {
    private $parseSchema;
    private $package;

    public function __construct($name, GitPackage $package, \GPM\Operations\ParseSchema $parseSchema)
    {
        $this->parseSchema = $parseSchema;
        $this->package = $package;

        parent::__construct($name);
    }


    protected function configure(): void
    {
        $this
            ->setDescription('Build classes from XML schema');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = new ConsoleLogger($output);
        $this->parseSchema->setLogger($logger);

        $this->parseSchema->execute($this->package);

        return Command::SUCCESS;
    }
}
