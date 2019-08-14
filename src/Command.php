<?php

namespace OsmScripts\Core;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @property string $defined_in @required
 * @property InputInterface $input
 * @property OutputInterface $output
 */
class Command extends BaseCommand
{
    public function __construct($data = []) {
        parent::__construct($data['name'] ?? null);

        foreach ($data as $property => $value) {
            $this->$property = $value;
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        /* @var Script $script */
        global $script;

        $script->command = $this;
        $script->input = $this->input = $input;
        $script->output = $this->output = $output;

        $this->handle();
    }

    protected function handle() {
    }
}