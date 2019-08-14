<?php

namespace OsmScripts\Core;

use Exception;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @property OutputInterface $output
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

        return null;
    }
    #endregion

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

    public function run($command, $quiet = false) {
        if (!$quiet) {
            $this->output->writeln("> {$command}");
        }

        passthru($command, $exitCode);
        if ($exitCode) {
            throw new Exception("Last command failed, error code: {$exitCode}");
        }
    }

    public function output($command) {
        exec($command, $output, $exitCode);
        if ($exitCode) {
            throw new Exception("Last command failed, error code: {$exitCode}");
        }

        return $output;
    }

}