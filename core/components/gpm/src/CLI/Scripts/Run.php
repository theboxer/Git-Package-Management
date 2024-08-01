<?php

namespace GPM\CLI\Scripts;

use GPM\CLI\Command;
use GPM\CLI\ConsoleLogger;
use GPM\Model\GitPackage;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Run extends Command {
    private $package;
    private $operation;

    public function __construct($name, GitPackage $package, \GPM\Operations\Scripts\Run $operation)
    {
        $this->package = $package;
        $this->operation = $operation;

        parent::__construct($name);
    }


    protected function configure(): void
    {
        $this
            ->addArgument('action', InputArgument::REQUIRED, 'Action to run scripts as: ' . \GPM\Operations\Scripts\Run::$allActions)
            ->addOption('scope', 's', InputOption::VALUE_REQUIRED, 'Scope of scripts: ' . \GPM\Operations\Scripts\Run::$allScopes, \GPM\Operations\Scripts\Run::SCOPE_ALL)
            ->addOption('name', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Name of scripts to run')
            ->setDescription('Runs all or specific gpm script');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = new ConsoleLogger($output);
        $this->operation->setLogger($logger);

        $action = $input->getArgument('action');
        $scope = $input->getOption('scope');
        $names = $input->getOption('name');

        $names = array_filter($names);

        if (!in_array($action, [\GPM\Operations\Scripts\Run::ACTION_INSTALL, \GPM\Operations\Scripts\Run::ACTION_UPGRADE, \GPM\Operations\Scripts\Run::ACTION_UNINSTALL])) {
            $logger->error('Invalid action. Use ' . \GPM\Operations\Scripts\Run::$allActions);
            return Command::FAILURE;
        }

        if (!empty($names) && $scope !== 'names') {
            $logger->error('Option name is only allowed when scope is set to "' . \GPM\Operations\Scripts\Run::SCOPE_NAMES . '"');
            return Command::FAILURE;
        }

        if (!in_array($scope, [\GPM\Operations\Scripts\Run::SCOPE_ALL, \GPM\Operations\Scripts\Run::SCOPE_BEFORE, \GPM\Operations\Scripts\Run::SCOPE_AFTER, \GPM\Operations\Scripts\Run::SCOPE_NAMES])) {
            $logger->error('Invalid scope. Use ' . \GPM\Operations\Scripts\Run::$allScopes);
            return Command::FAILURE;
        }

        if ($scope === \GPM\Operations\Scripts\Run::SCOPE_NAMES && empty($names)) {
            $logger->error('Supply at least one `--name` option.');
            return Command::FAILURE;
        }

        $this->operation->execute($this->package, $action, $scope, $names);

        return Command::SUCCESS;
    }
}