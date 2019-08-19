<?php

namespace OsmScripts\Core\Commands;

use OsmScripts\Core\Command;
use OsmScripts\Core\Script;
use OsmScripts\Core\Variables;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/** @noinspection PhpUnused */

/**
 * @property Variables $variables Helper for managing script variables
 * @property string $variable_help Description of known variables for the script, taken from
 *      script configuration
 */
class Var_ extends Command
{
    #region Properties
    public function __get($property) {
        /* @var Script $script */
        global $script;

        switch ($property) {
            case 'variables': return $this->variables = $script->singleton(Variables::class);
            case 'variable_help': return $this->variable_help = $this->getVariableHelp();
        }

        return null;
    }

    protected function getVariableHelp() {
        /* @var Script $script */
        global $script;

        $result = "";
        foreach ($script->config->variables ?? [] as $name => $description) {
            $result .= "* {$name} - {$description}\n";
        }

        if ($result) {
            $result = "Known variables:\n\n{$result}";
        }
        return $result;
    }

    #endregion

    protected function configure() {
        $this
            ->setDescription("Gets, sets, or clears script variables")
            ->setHelp($this->variable_help)
            ->addArgument('variable', InputArgument::IS_ARRAY,
                "'VAR' shows the variable, 'VAR=' clears the variable, 'VAR=value' sets the variable");
    }

    protected function handle() {
        $arguments = $this->input->getArgument('variable');
        if (empty($arguments)) {
            foreach ($this->variables->all() as $variable => $value) {
                $this->output->writeln("$variable=$value");
            }
        }
        else {
            foreach ($arguments as $argument) {
                if (($pos = strpos($argument, '=')) === false) {
                    $this->output->writeln("$argument={$this->variables->get($argument)}");
                }
                else {
                    $value = substr($argument, $pos + 1);
                    $argument = substr($argument, 0, $pos);
                    if ($value === '') {
                        $this->variables->unset($argument);
                    }
                    else {
                        $this->variables->set($argument, $value);
                    }
                    $this->output->writeln("$argument={$this->variables->get($argument)}");
                }
            }
        }
    }
}