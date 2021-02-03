<?php
namespace GPM\CLI;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PackageBuild extends Command {
    protected static $defaultName = 'package:build';

    /** @var \GPM\Operations\Build */
    private $build;

    public function __construct(\GPM\Operations\Build $build)
    {
        $this->build = $build;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Build package without installing it')
            ->addArgument('dir', InputArgument::REQUIRED, 'Directory name where the new package is located')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = new ConsoleLogger($output);
        $this->build->setLogger($logger);

        $dir = $input->getArgument('dir');
        $this->build->execute($dir);

        return Command::SUCCESS;
    }
}
