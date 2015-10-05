<?php
namespace GPM\CLI\Commands\Package;

use GPM\CLI\Commands\PackageCommand;
use GPM\Config\Config;
use GPM\Config\Loader\JSON;
use GPM\Config\Parser\Parser;
use GPM\Config\Validator\ValidatorException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class Build extends PackageCommand
{
    protected function configure()
    {
        $this
            ->setDescription('Build a transport package.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $config = new Config($this->getApplication()->modx, $this->package->dir_name);
            $parser = new Parser($this->getApplication()->modx, $config);
            $loader = new JSON($parser);
            $loader->loadAll();

            $builder = new \GPM\Action\Build($config, $logger);
            $builder->build();
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
