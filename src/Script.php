<?php

namespace OsmScripts\Core;

use Exception;
use OsmScripts\Core\Hints\ComposerLockHint;
use OsmScripts\Core\Hints\ConfigHint;
use OsmScripts\Core\Hints\PackageHint;
use stdClass;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class for currently executed script. Contains all the objects: helpers which provide useful APIs,
 * console application with its commands, knowledge about this project's packages and more.
 *
 * Script configures itself from the section of `composer.json` files having the same specified in `name` property.
 *
 * Currently executed script instance is accessible via global read-only `$script` variable.
 *
 * @property string $name @required Script name
 * @property string $path @required Directory of the Composer project containing the script
 * @property string $cwd @required Current working directory - a directory from which the script is invoked
 * @property object|ComposerLockHint $composer_lock @required Contents of project's `composer.lock` file
 * @property object[]|PackageHint[] $installed_packages @required Package information from `composer.lock` file
 * @property string[] $package_names @required Names of currently installed packages
 * @property object[]|PackageHint[] $packages @required Package information from package `composer.json` files
 * @property object|ConfigHint $config @required Script configuration, merged from all package `composer.json` files
 * @property Application $application @required Symfony console application instance helping with
 *      reading command-line arguments, dispatching to correct command class and outputting to console
 *
 * @property Command $command Currently executed command. Only available since command execution is started
 * @property InputInterface $input Command-line arguments and options user passed to this command.
 *      Only available since command execution is started
 * @property OutputInterface $output Output console. Only available since command execution is started
 */
class Script extends Object_
{
    #region Properties
    public function __get($property) {
        switch ($property) {
            case 'path': return $this->path = dirname(dirname(dirname(dirname(__DIR__))));
            case 'composer_lock': return $this->composer_lock = $this->getComposerLock();
            case 'installed_packages': return $this->installed_packages = $this->getInstalledPackages();
            case 'package_names': return $this->package_names = array_keys($this->installed_packages);
            case 'packages': return $this->packages = $this->getPackages();
            case 'config': return $this->config = $this->getConfig();
            case 'application': return $this->application = $this->getApplication();
        }

        return null;
    }

    protected function getComposerLock() {
        return $this->readJson("{$this->path}/composer.lock");
    }

    protected function getInstalledPackages() {
        $result = [];

        foreach ($this->composer_lock->packages ?? [] as $package) {
            $result[$package->name] = $package;
        }

        return $result;
    }

    protected function getPackages() {
        $result = [];

        foreach ($this->package_names as $name) {
            $result[$name] = $this->readJson("{$this->path}/vendor/{$name}/composer.json");
        }

        return $result;
    }

    protected function getConfig() {
        $result = new stdClass();

        foreach ($this->packages as $name => $package) {
            $commands = isset($package->extra->commands) ? (array)$package->extra->commands : [];

            /* @var ConfigHint $config */
            $config = $package->extra->{$this->name} ?? new stdClass();

            if (isset($config->commands)) {
                $commands = array_merge($commands, (array)$config->commands);
            }

            $config->commands = array_map(function($class) use ($name){
                return (object)['package' => $name, 'class' => $class];
            }, $commands);

            $result = $this->mergeConfig($result, $config);
        }

        return $result;
    }

    protected function getApplication() {
        $app = new Application($this->config->name ?? "{$this->name} Script");

        foreach ($this->config->commands as $name => $command) {
            /* @var Command $command_*/
            $command_ = new $command->class(['name' => $name]);
            $command_->defined_in = $command->package;
            $app->add($command_);
        }

        return $app;
    }
    #endregion

    protected $singletons = [];

    /**
     * Returns an instance of specified class, the same for all callers.
     *
     * @param string $class Full class name
     * @return mixed
     */
    public function singleton($class) {
        if (!isset($this->singletons[$class])) {
            $this->singletons[$class] = new $class();
        }

        return $this->singletons[$class];
    }

    /**
     * Executes this script. Under the hood, executes requested Command
     *
     * @return int
     */
    public function run() {
        return $this->application->run();
    }

    protected function readJson($filename) {
        if (!($contents = file_get_contents($filename))) {
            throw new Exception("'{$filename}' not found");
        }

        if (!($result = json_decode($contents))) {
            throw new Exception("'{$filename}' is not valid JSON file");
        }

        return $result;
    }

    protected function mergeConfig($target, $source) {
        if (is_object($target)) {
            foreach ($source as $key => $value) {
                if (property_exists($target, $key)) {
                    $target->$key = $this->mergeConfig($target->$key, $value);
                }
                else {
                    $target->$key = $value;
                }
            }

            return $target;
        }
        elseif (is_array($target)) {
            foreach ($source as $key => $value) {
                if (is_numeric($key)) {
                    $target[] = $value;
                }
                elseif (isset($target[$key])) {
                    $target[$key] = $this->mergeConfig($target[$key], $value);
                }
                else {
                    $target[$key] = $value;
                }
            }

            return $target;
        }
        else {
            return $source;
        }
    }
}