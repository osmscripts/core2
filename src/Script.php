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
 * @property string $name @required
 * @property string $path @required
 * @property string $cwd @required
 * @property object|ComposerLockHint $composer_lock @required
 * @property object[]|PackageHint[] $installed_packages @required Package information from `composer.lock` file
 * @property string[] $package_names @required
 * @property object[]|PackageHint[] $packages @required Package information from package `composer.json` files
 * @property object|ConfigHint $config @required
 * @property Application $application @required
 *
 * @property Command $command
 * @property InputInterface $input
 * @property OutputInterface $output
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

    public function singleton($class) {
        if (!isset($this->singletons[$class])) {
            $this->singletons[$class] = new $class();
        }

        return $this->singletons[$class];
    }

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