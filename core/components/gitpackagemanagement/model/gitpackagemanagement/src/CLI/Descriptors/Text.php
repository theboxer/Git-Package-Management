<?php
namespace GPM\CLI\Descriptors;

use GPM\Utils;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Descriptor\ApplicationDescription;
use Symfony\Component\Console\Descriptor\TextDescriptor;
use Symfony\Component\Console\Input\InputDefinition;

class Text extends TextDescriptor
{
    protected function describeApplication(Application $application, array $options = array())
    {
        $describedNamespace = isset($options['namespace']) ? $options['namespace'] : null;
        $description = new ApplicationDescription($application, $describedNamespace);

        if (isset($options['raw_text']) && $options['raw_text']) {
            $width = $this->getColumnWidth($description->getCommands());

            foreach ($description->getCommands() as $command) {
                $this->writeText(sprintf("%-${width}s %s", $command->getName(), $command->getDescription()), $options);
                $this->writeText("\n");
            }
        } else {
            if ('' != $help = $application->getHelp()) {
                $this->writeText("$help\n\n", $options);
            }

            $this->writeText("<comment>Usage:</comment>\n", $options);
            $this->writeText("  command [options] [arguments]\n\n", $options);

            $this->describeInputDefinition(new InputDefinition($application->getDefinition()->getOptions()), $options);

            $this->writeText("\n");
            $this->writeText("\n");

            $width = $this->getColumnWidth($description->getCommands());

            $profile = Utils::loadProfile();
            if ($profile === false) {
                if ($describedNamespace) {
                    $this->writeText(sprintf('<comment>Available commands for the "%s" namespace:</comment>', $describedNamespace), $options);
                } else {
                    $this->writeText('<comment>Available commands:</comment>', $options);
                }
            } else {
                if ($describedNamespace) {
                    $this->writeText(sprintf('<comment>Available commands for the "%s" namespace in </comment><info>%s </info><comment>profile:</comment>', $describedNamespace, $profile['name']), $options);
                } else {
                    $this->writeText(sprintf('<comment>Available commands in </comment><info>%s </info><comment>profile:</comment>', $profile['name']), $options);
                }                
            }

            // add commands by namespace
            foreach ($description->getNamespaces() as $namespace) {
                if (!$describedNamespace && ApplicationDescription::GLOBAL_NAMESPACE !== $namespace['id']) {
                    $this->writeText("\n");
                    $this->writeText(' <comment>'.$namespace['id'].'</comment>', $options);
                }

                foreach ($namespace['commands'] as $name) {
                    $this->writeText("\n");
                    $spacingWidth = $width - strlen($name);
                    $this->writeText(sprintf('  <info>%s</info>%s%s', $name, str_repeat(' ', $spacingWidth), $description->getCommand($name)->getDescription()), $options);
                }
            }

            $this->writeText("\n");
        }
    }

    private function writeText($content, array $options = array())
    {
        $this->write(
            isset($options['raw_text']) && $options['raw_text'] ? strip_tags($content) : $content,
            isset($options['raw_output']) ? !$options['raw_output'] : true
        );
    }

    private function getColumnWidth(array $commands)
    {
        $widths = array();

        foreach ($commands as $command) {
            $widths[] = strlen($command->getName());
            foreach ($command->getAliases() as $alias) {
                $widths[] = strlen($alias);
            }
        }

        return max($widths) + 2;
    }
}