<?php

namespace OsmScripts\Core;

use Exception;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Helper class for running commands in local shell
 *
 * @property OutputInterface $output Output console
 */
class Shell extends Object_
{
    #region Properties
    public function __get($property) {
        /* @var Script $script */
        global $script;

        switch ($property) {
            case 'output': return $this->output = $script->output;
        }

        return parent::__get($property);
    }
    #endregion

    /**
     * Change current directory to `$path`, execute `$callback` and then restore current directory
     *
     * @param string $path Directory to switch to
     * @param callable $callback Callback function to be executed in specified directory
     * @param bool $quiet Set to true to prevent notifying user about switching directories
     */
    public function cd($path, callable $callback, $quiet = false) {
        $cwd = getcwd();
        if (!$quiet) {
            $this->output->writeln("> cd {$path}");
        }
        chdir($path);

        try {
            $callback();
        }
        finally {
            if (!$quiet) {
                $this->output->writeln("> cd {$cwd}");
            }
            chdir($cwd);
        }
    }

    /**
     * Runs specified shell command. If command fails, this script stops.
     *
     * @param string $command Command to be executed
     * @param bool $quiet Set to true to prevent notifying user about running this command
     */
    public function run($command, $quiet = false) {
        if (!$quiet) {
            $this->output->writeln("> {$command}");
        }

        passthru($command, $exitCode);
        if ($exitCode) {
            throw new Exception("Last command failed, error code: {$exitCode}");
        }
    }

    /**
     * Runs specified shell command and returns its output as array of strings.
     * If command fails, this script stops.
     *
     * @param string $command Command to be executed
     * @return string[]
     */
    public function output($command) {
        exec($command, $output, $exitCode);
        if ($exitCode) {
            throw new Exception("Last command failed, error code: {$exitCode}");
        }

        return $output;
    }

}