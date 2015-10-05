<?php
namespace GPM\CLI\Commands\Package;

use Symfony\Component\Console\Command\ListCommand;
use Symfony\Component\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CMDList extends ListCommand
{
    public function getNativeDefinition()
    {
        return $this->createDefinition();
    }

    protected function configure()
    {
        $this
            ->setDefinition($this->createDefinition())
            ->setDescription('Lists commands')
            ->setHelp(<<<EOF
The <info>%command.name%</info> command lists all commands:

  <info>php %command.full_name%</info>

You can also output the information in other formats by using the <comment>--format</comment> option:

  <info>php %command.full_name% --format=xml</info>

It's also possible to get raw list of commands (useful for embedding command runner):

  <info>php %command.full_name% --raw</info>
EOF
            );
    }

    private function createDefinition()
    {
        return new InputDefinition(array(
            new InputOption('xml', null, InputOption::VALUE_NONE, 'To output list as XML'),
            new InputOption('raw', null, InputOption::VALUE_NONE, 'To output raw command list'),
            new InputOption('format', null, InputOption::VALUE_REQUIRED, 'The output format (txt, xml, json, or md)', 'txt'),
        ));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('xml')) {
            @trigger_error('The --xml option was deprecated in version 2.7 and will be removed in version 3.0. Use the --format option instead.', E_USER_DEPRECATED);

            $input->setOption('format', 'xml');
        }

        $helper = new DescriptorHelper();
        $helper->describe($output, $this->getApplication(), array(
            'format' => $input->getOption('format'),
            'raw_text' => $input->getOption('raw'),
            'namespace' => $this->getName(),
        ));
    }
}
