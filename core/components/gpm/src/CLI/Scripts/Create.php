<?php
namespace GPM\CLI\Scripts;

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

    public function __construct($name, GitPackage $package, \GPM\Operations\Scripts\Create $operation)
    {
        $this->package = $package;
        $this->operation = $operation;

        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Create new script')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the script (.gpm.php will be appended automatically)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = new ConsoleLogger($output);
        $this->operation->setLogger($logger);

        $name = $input->getArgument('name');

        $this->operation->execute($this->package, $name);

        return Command::SUCCESS;
    }
}
