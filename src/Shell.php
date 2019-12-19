<?php

namespace OsmScripts\Core;

use Exception;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Helper class for running commands in local shell
 *
 * @property OutputInterface $output Output console
 * @property string $user User account to run commands with
 */
class Shell extends Object_
{
    #region Properties
    public function default($property) {
        /* @var Script $script */
        global $script;

        switch ($property) {
            case 'output': return $script->output;
        }

        return parent::default($property);
    }
    #endregion

    /**
     * Change current directory to `$path`, execute `$callback` and then restore current directory
     *
     * @param string $path Directory to switch to
     * @param callable $callback Callback function to be executed in specified directory
     * @param bool $quiet Set to true to prevent notifying user about switching directories
     * @return mixed
     */
    public function cd($path, callable $callback, $quiet = false) {
        if (!$path) {
            return $callback();
        }

        $cwd = getcwd();
        if (!$quiet) {
            $this->output->writeln("> cd {$path}");
        }
        chdir($path);

        try {
            return $callback();
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

        if ($this->user && PHP_OS_FAMILY == 'Linux') {
            $command = "su -s /bin/bash -c " . escapeshellarg($command) . " {$this->user}";
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

    public function su($user, callable $callback) {
        $oldUser = $this->user;
        $this->user = $user;

        try {
            $callback();
        }
        finally {
            $this->user = $oldUser;
        }
    }
}