<?php

namespace OsmScripts\Core;

use OsmScripts\Core\Hints\PackageHint;
use stdClass;

/**
 * @property string $name @required
 * @property Project $project @required
 * @property PackageHint $lock @required
 * @property PackageHint $json @required
 * @property string $path @required
 * @property string $namespace @required
 *
 * @property Utils $utils @required various helper functions
 */
class Package extends Object_
{
    #region Properties
    public function __get($property) {
        /* @var Script $script */
        global $script;

        switch ($property) {
            case 'lock': return $this->lock = new stdClass();
            case 'name': return $this->name = $this->lock->name;
            case 'path': return $this->path = "vendor/{$this->name}";
            case 'json': return $this->json =
                $this->utils->readJsonOrFail("{$this->project->path}/{$this->path}/composer.json");
            case 'namespace': return $this->namespace = $this->getNamespace();

            case 'utils': return $this->utils = $script->singleton(Utils::class);
        }

        return null;
    }

    protected function getNamespace() {
        foreach ($this->json->autoload->{"psr-4"} ?? new stdClass() as $namespace => $path) {
            if ($path === 'src/') {
                return rtrim($namespace, "\\");
            }
        }

        throw new \Exception("Package '{$this->name}' is expected to have " .
            "'autoload.psr-4' section in its 'composer.json' file");
    }
    #endregion
}