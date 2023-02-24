<?php
namespace GPM\CLI;

use GPM\Model\GitPackage;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class KeyAdd extends Command {
    private $keyAdd;
    private $package;

    public function __construct($name, GitPackage $package, \GPM\Operations\Key\Add $operation)
    {
        $this->keyAdd = $operation;
        $this->package = $package;

        parent::__construct($name);
    }


    protected function configure(): void
    {
        $this
            ->setDescription('Adds new API key')
            ->addOption('update', '', InputOption::VALUE_NONE, 'Allow update')
            ->addOption('updateWithAlter', '', InputOption::VALUE_NONE, 'Allow update with alter DB')
            ->addOption('updateWithRecreate', '', InputOption::VALUE_NONE, 'Allow update with recreate DB')
            ->addOption('build', '', InputOption::VALUE_NONE, 'Allow build')
            ->addOption('remove', '', InputOption::VALUE_NONE, 'Allow remove');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = new ConsoleLogger($output);
        $this->keyAdd->setLogger($logger);

        $update = $input->getOption('update');
        $updateWithAlter = $input->getOption('updateWithAlter');
        $updateWithRecreate = $input->getOption('updateWithRecreate');
        $build = $input->getOption('build');
        $remove = $input->getOption('remove');

        $permissions = [];

        if ($update) {
            $permissions['update'] = [];

            if ($updateWithAlter) {
                $permissions['update']['alterDB'] = [];
            }

            if ($updateWithRecreate) {
                $permissions['update']['recreateDB'] = [];
            }
        }

        if ($build) {
            $permissions['build'] = [];
        }

        if ($remove) {
            $permissions['remove'] = [];
        }

        $this->keyAdd->execute($this->package, $permissions);

        return Command::SUCCESS;
    }
}
