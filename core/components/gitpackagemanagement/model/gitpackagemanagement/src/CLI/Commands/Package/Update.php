<?php
namespace GPM\CLI\Commands\Package;

use GPM\CLI\Commands\GPMCommand;
use GPM\Config\Config;
use GPM\Config\Loader\JSON;
use GPM\Config\Parser\Parser;
use GPM\Config\Validator\ValidatorException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class Update extends GPMCommand
{
    protected function configure()
    {
        $this
            ->setDescription('Update a package')
            ->addOption(
                'updateDB',
                null,
                InputOption::VALUE_OPTIONAL,
                'If passed database will be updated. Possible options: alter, recreate or empty value',
                ''
            )
            ->addOption(
                'schema',
                null,
                InputOption::VALUE_NONE,
                'If passed XML schema will be build'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
        $logger = new ConsoleLogger($output);

        try {
            $config = new Config($this->getApplication()->modx, $this->package->dir_name);
            $parser = new Parser($this->getApplication()->modx, $config);
            $loader = new JSON($parser);
            $loader->loadAll();

            $schema = new \GPM\Action\Update($config, $this->package, $logger);
            $schema->update($input->getOption('updateDB'), intval($input->getOption('schema')));
        } catch (ValidatorException $ve) {
            $logger->error('Config file is invalid.');
            $logger->error($ve->getMessage());


            return null;
        } catch (\Exception $e) {
            $logger->error($e->getMessage());

            return null;
        }
    }
}
