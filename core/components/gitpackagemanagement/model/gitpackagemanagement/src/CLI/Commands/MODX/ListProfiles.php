<?php
namespace GPM\CLI\Commands\MODX;

use GPM\CLI\Commands\Command;
use GPM\Utils;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class ListProfiles extends Command
{

    protected function configure()
    {
        $this
            ->setName('modx:list')
            ->setDescription('List existing MODX profiles')
        ;
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
        $logger = new ConsoleLogger($output);

        $finder = new Finder();
        /** @var \Symfony\Component\Finder\SplFileInfo[] $profiles */
        $profiles = $finder->files()->in($profilesDir = Utils::getProfilesDir());
        
        foreach ($profiles as $profile) {
            $output->writeln($profile->getBasename('.json'));
        }
        
    }
}
