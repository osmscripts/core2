<?php

namespace OsmScripts\Core;

use OsmScripts\Core\Hints\PackageHint;
use stdClass;

/**
 * @property string $name @required
 * @property Project $project @required
 * @property PackageHint $lock @required
 * @property PackageHint $json @required
 * @property object $config @required
 * @
 * @property string $path @required
 * @property string $namespace @required
 *
 * @property Utils $utils @required various helper functions
 */
class Package extends Object_
{
    #region Properties
    public function default($property) {
        /* @var Script $script */
        global $script;

        switch ($property) {
            case 'lock': return new stdClass();
            case 'name': return $this->lock->name;
            case 'path': return "vendor/{$this->name}";
            case 'json': return $this->utils->readJsonOrFail("{$this->project->path}/{$this->path}/composer.json");
            case 'config': return $this->utils->readJson("{$this->project->path}/{$this->path}/osmscripts.json")
                ?: (object)[];
            case 'namespace': return $this->getNamespace();

            case 'utils': return $script->singleton(Utils::class);
        }

        return parent::default($property);
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