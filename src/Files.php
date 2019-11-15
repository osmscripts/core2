<?php

namespace OsmScripts\Core;

use Exception;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Helper class for generating files.
 *
 * @property Command $command @required Currently executed command
 * @property string $path @required Directory of the Composer project containing currently executed script
 * @property string $script @required Currently executed script
 * @property OutputInterface $output @required Output console
 */
class Files extends Object_
{
    #region Properties
    public function default($property) {
        /* @var Script $script */
        global $script;

        switch ($property) {
            case 'command': return $script->command;
            case 'path': return $script->path;
            case 'script': return $script->name;
            case 'output': return $script->output;
        }

        return parent::default($property);
    }
    #endregion

    /**
     * Render specified template of the current package with specified variable values and
     * return the result as a string.
     *
     * Write the template using plain PHP in `{package_dir}/templates/{script}/{template}.php`.
     *
     * Override template for your needs in `{project_dir}/.osmscripts/{package}/templates/{script}/{template}.php`
     *
     * @param string $template Template name. Use `/` for hierarchical template names
     * @param array $variables Values which will be inserted instead of variable placeholders inside the template
     * @param string $package name of the package which defines the template. If omitted,
     *      template is taken from the package in which currently executed command is defined
     * @return string
     */
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

    /**
     * Create file with specified contents or overwrite if it already exists.
     *
     * @param string $filename Filename
     * @param string $contents Contents
     */
    public function save($filename, $contents) {
        $action = is_file($filename) ? 'updated' : 'created';

        if (!is_dir(dirname($filename))) {
            mkdir(dirname($filename), 0775, true);
        }

        file_put_contents($filename, $contents);

        $this->output->writeln("! {$filename} {$action}");
    }

    public function createLink($target, $link) {
        if (file_exists($link)) {
            return;
        }

        if (!file_exists(realpath($target))) {
            return;
        }

        if (!is_dir(dirname($link))) {
            mkdir(dirname($link), 0775, true);
        }

        symlink(realpath($target), $link);
        $this->output->writeln("! {$link} => {$target} link created");
    }

    public function deleteLink($link) {
        if (!is_link($link)) {
            return;
        }

        unlink($link);
        $this->output->writeln("! {$link} link deleted");
    }
}