<?php
namespace GPM\CLI\Commands\MODX;

use GPM\CLI\Commands\Command;
use GPM\Utils;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class Profile extends Command
{

    protected function configure()
    {
        $this
            ->setName('modx:profile')
            ->setDescription('Create MODX profile')
            ->addOption(
                'name',
                null,
                InputOption::VALUE_REQUIRED,
                'Profile name'
            )
            ->addOption(
                'core_path',
                null,
                InputOption::VALUE_REQUIRED,
                'Absolute path to the MODX core'
            )
            ->addOption(
                'config_key',
                null,
                InputOption::VALUE_REQUIRED,
                'MODX config key',
                'config'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE
            )
        ;
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
        $logger = new ConsoleLogger($output);
        
        // @TODO name validation
        $name = $input->getOption('name');
        if (empty($name)) {
            $logger->error('Option name is required.');
            return;
        }
        
        $corePath = $input->getOption('core_path');
        if (empty($corePath)) {
            $logger->error('Option core_path is required.');
            return;
        }
        
        $configKey = $input->getOption('config_key');
        if (empty($configKey)) {
            $logger->error('Option config_key is required.');
            return;
        }

        $force = $input->getOption('force');

        $profile = [
            'name' => $name,
            'core_path' => $corePath,
            'config_key' => $configKey
        ];
        
        $profilesDir = Utils::getProfilesDir();
        if (!file_exists($profilesDir . '/' . $name . '.json') || $force) {
            file_put_contents($profilesDir . '/' . $name . '.json', json_encode($profile));
        } else {
            $logger->warning('Profile with this names exists. Use --force to override it.');
        }
        
    }
}
