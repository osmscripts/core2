<?php

namespace OsmScripts\Core;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Base class for script commands.
 *
 * @property string $defined_in @required Name of Composer package this command is defined in
 * @property InputInterface $input Command-line arguments and options user passed to this command
 * @property OutputInterface $output Output console
 */
class Command extends BaseCommand
{
    public function __construct($data = []) {
        parent::__construct($data['name'] ?? null);

        // allows user code to inject custom property values to be used instead of calculated ones
        foreach ($data as $property => $value) {
            $this->$property = $value;
        }
    }

    public function __get($property) {
        return null;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        /* @var Script $script */
        global $script;

        // make execution context (which command runs, what command-line arguments
        // have been passed, console for the output) available to all other objects
        $script->command = $this;
        $script->input = $this->input = $input;
        $script->output = $this->output = $output;

        $this->handle();
    }

    protected function handle() {
        // by default, commands don't do anything. Override this method and put actual
        // command logic there
    }
}