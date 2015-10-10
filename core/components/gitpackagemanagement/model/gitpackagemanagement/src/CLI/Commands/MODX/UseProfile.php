<?php
namespace GPM\CLI\Commands\MODX;

use GPM\CLI\Commands\Command;
use GPM\Utils;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class UseProfile extends Command
{

    protected function configure()
    {
        $this
            ->setName('modx:use')
            ->setDescription('Switch to different MODX profile')
            ->addArgument(
                'profile',
                InputArgument::OPTIONAL,
                'Profile name'
            )
        ;
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
        $logger = new ConsoleLogger($output);
        $helper = $this->getHelper('question');
        
        $profilesDir = Utils::getProfilesDir();
        
        $profile = $input->getArgument('profile');
        if (empty($profile)) {
            $finder = new Finder();
            /** @var \Symfony\Component\Finder\SplFileInfo[] $profiles */
            $profiles = $finder->files()->in($profilesDir);

            $availableProfiles = [];
            foreach ($profiles as $profile) {
                $availableProfiles[] = $profile->getBasename('.json');
            }
            
            $question = new ChoiceQuestion(
                'Please select MODX profile',
                $availableProfiles
            );

            $question->setErrorMessage('Profile name %s is invalid.');

            $profile = $helper->ask($input, $output, $question);
        }
        
        $profileConfig = Utils::loadProfile($profile);

        if ($profileConfig === false) {
            $logger->error('Profile with this names doesn\'t exist.');
            return;
        }
        
        $config = <<<EOD
<?php
define("MODX_CORE_PATH", "{$profileConfig["core_path"]}");
define("MODX_CONFIG_KEY", "{$profileConfig["config_key"]}");
EOD;

        $fs = new Filesystem();
        $fs->dumpFile(dirname($profilesDir) . '/config.core.php', $config);
        $fs->dumpFile(dirname($profilesDir) . '/.current_profile', json_encode($profileConfig));
        
        $output->writeln('Profile switched.');
    }
}
