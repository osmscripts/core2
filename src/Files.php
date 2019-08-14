<?php

namespace OsmScripts\Core;

use Exception;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @property Command $command @required
 * @property string $path @required
 * @property string $script @required
 * @property OutputInterface $output @required
 */
class Files extends Object_
{
    #region Properties
    public function __get($property) {
        /* @var Script $script */
        global $script;

        switch ($property) {
            case 'command': return $this->command = $script->command;
            case 'path': return $this->path = $script->path;
            case 'script': return $this->script = $script->name;
            case 'output': return $this->output = $script->output;
        }

        return null;
    }
    #endregion

    public function render($template, $variables = [], $package = null) {
        if (!$package) {
            $package = $this->command->defined_in;
        }

        $filename = "{$this->path}/vendor/$package/templates/{$this->script}/$template.php";
        if (!is_file($filename)) {
            throw new Exception("Template '{$filename}' not found");
        }

        $overwrite = "{$this->path}/.osmscripts/$package/templates/{$this->script}/$template.php";
        if (is_file($overwrite)) {
            $filename = $overwrite;
        }

        return $this->doRender($filename, $variables);
    }

    protected function doRender($__filename, $__variables = []) {
        extract($__variables);
        ob_start();

        /** @noinspection PhpIncludeInspection */
        include $__filename;

        return ob_get_clean();
    }

    public function save($path, $contents) {
        $action = is_file($path) ? 'updated' : 'created';

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, $contents);

        $this->output->writeln("! {$path} {$action}");
    }
}