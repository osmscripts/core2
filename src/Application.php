<?php

namespace OsmScripts\Core;

use Exception;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @property string $global
 */
class Application extends BaseApplication
{
    public function setWorkDir(InputInterface $input = null, OutputInterface $output = null) {
        /* @var Script $script */
        global $script;

        if (null === $input) {
            $input = new ArgvInput();
        }

        if (null === $output) {
            $output = new ConsoleOutput();
        }

        if ($cwd = $input->getParameterOption(['--work-dir'], false, true)) {
            if (!is_dir($cwd)) {
                throw new Exception("'{$cwd}' is not a valid directory");
            }

            $this->cd($cwd, $output);
        } elseif ($script->global == Script::GLOBAL_UPON_REQUEST &&
            ($input->hasParameterOption(['--global', '-g'], true) === true)) {
            $this->cd($script->path, $output);
        }
    }

    protected function getDefaultInputDefinition()
    {
        /* @var Script $script */
        global $script;

        $definition = [
            new InputArgument('command', InputArgument::REQUIRED,
                'The command to execute'),

            new InputOption('--help', '-h', InputOption::VALUE_NONE,
                'Display this help message'),
            new InputOption('--verbose', '-v|vv|vvv', InputOption::VALUE_NONE,
                'Increase the verbosity of messages: 1 for normal output, ' .
                '2 for more verbose output and 3 for debug'),
            new InputOption('--version', '-V', InputOption::VALUE_NONE,
                'Display this application version'),
        ];

        if ($script->global == Script::GLOBAL_UPON_REQUEST) {
            $definition[] = new InputOption('--global', '-g', InputOption::VALUE_NONE,
                'Work in global Composer installation directory rather than in current directory.');
        }

        $definition[] = new InputOption(
            '--work-dir', null, InputOption::VALUE_OPTIONAL,
            'Work in specified directory rather than in current directory.'
        );

        return new InputDefinition($definition);
    }

    protected function cd($path, OutputInterface $output) {
        chdir($path);
        $output->writeln("> cd {$path}");
    }
}