<?php
namespace GPM\CLI\Commands\Package;

use GPM\CLI\Commands\GPMCommand;
use GPM\Config\Config;
use GPM\Config\Loader\JSON;
use GPM\Config\Parser\Parser;
use GPM\Config\Validator\ValidatorException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class Install extends GPMCommand
{
    protected function configure()
    {
        $this
            ->setName('package:install')
            ->setDescription('Install a new package')
            ->addOption(
                'dir',
                null,
                InputOption::VALUE_REQUIRED,
                'Name of the folder with package'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
        $logger = new ConsoleLogger($output);
        
        try {
            $config = new Config($this->getApplication()->modx, $input->getOption('dir'));
            $parser = new Parser($this->getApplication()->modx, $config);
            $loader = new JSON($parser);
            $loader->loadAll();

            $installer = new \GPM\Action\Install($config, $logger);
            $installer->install();
        } catch (ValidatorException $ve) {
            $logger->error('Config file is invalid.');
            $logger->error($ve->getMessage());

            return null;
        } catch (\Exception $e) {
            $logger->error($e->getMessage());

            return null;
        }

        $output->writeln('Package installed.');
    }
}
