<?php
namespace GPM\CLI;

use GPM\Config\Parts\General;
use GPM\Config\Parts\Part;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class PackageCreate extends Command {
    protected static $defaultName = 'package:create';

    /** @var \GPM\Operations\Create */
    private $create;

    public function __construct(\GPM\Operations\Create $create)
    {
        $this->create = $create;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Create new Package')
            ->addArgument('dir', InputArgument::REQUIRED, 'Directory name where the new package is located')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the package')
            ->addOption('packageVersion', '', InputOption::VALUE_REQUIRED, 'Version in a format x.y.z-rl', '1.0.0-pl')

            ->addOption('lowCaseName', 'l', InputOption::VALUE_REQUIRED, 'Name of the package in lower case (without spaces and extra symbols), used for inner dirs and modx namespace', '')
            ->addOption('namespace', 's', InputOption::VALUE_REQUIRED, 'PSR-4 Namespace of the package', '')
            ->addOption('description', null, InputOption::VALUE_REQUIRED, 'What\'s this package about', '')
            ->addOption('author', null, InputOption::VALUE_REQUIRED, 'Name of the package\'s author (possibly you)', '')

            ->addOption('withComposer', 'c', InputOption::VALUE_NONE, 'Include custom composer.json file')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Delete the package directory before creating new one')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = new ConsoleLogger($output);
        $this->create->setLogger($logger);

        $helper = $this->getHelper('question');

        $dir = $input->getArgument('dir');
        $name = $input->getArgument('name');

        $withComposer = $input->getOption('withComposer');
        $force = $input->getOption('force');

        $version = $input->getOption('packageVersion');
        $lowCaseName = $input->getOption('lowCaseName');
        $namespace = $input->getOption('namespace');
        $description = $input->getOption('description');
        $author = $input->getOption('author');

        $general = new General(
            [
                'name' => $name,
                'lowCaseName' => $lowCaseName,
                'namespace' => $namespace,
            ]
        );

        if (empty($version) || ($version === '1.0.0-pl')) {
            $question = new Question('Package version (default: 1.0.0-pl): ', '1.0.0-pl');
            $version = $helper->ask($input, $output, $question);
        }

        if (empty($lowCaseName)) {
            $question = new Question('Name of the package in lower case (without spaces and extra symbols, default: ' . $general->lowCaseName . '): ', $general->lowCaseName);
            $lowCaseName = $helper->ask($input, $output, $question);
        }

        if (empty($namespace)) {
            $question = new Question('PSR-4 Namespace of the package (default: ' . $general->namespace . '): ', $general->namespace);
            $namespace = $helper->ask($input, $output, $question);
        }

        if (empty($author)) {
            $question = new Question('Name of the package\'s author (possibly you): ', '');
            $author = $helper->ask($input, $output, $question);
        }

        if (empty($description)) {
            $question = new Question('What\'s this package about: ', '');
            $description = $helper->ask($input, $output, $question);
        }

        $general = [
            'name' => $name,
            'version' => $version,
        ];

        if (!empty($lowCaseName)) {
            $general['lowCaseName'] = $lowCaseName;
        }

        if (!empty($namespace)) {
            $general['namespace'] = $namespace;
        }

        if (!empty($description)) {
            $general['description'] = $description;
        }

        if (!empty($author)) {
            $general['author'] = $author;
        }

        $this->create->execute($dir, $general, $withComposer, $force);

        return Command::SUCCESS;
    }
}
