<?php

namespace OsmScripts\Core;

use Exception;
use OsmScripts\Core\Hints\ComposerLockHint;
use OsmScripts\Core\Hints\PackageHint;

/**
 * Information about Composer project in $path directory
 *
 * @property string $path @required Project's path
 * @property object|ComposerLockHint $lock @required Contents of project's `composer.lock` file
 * @property Package[] $packages @required Package information from package `composer.json` files
 * @property bool $current @required True if currently executed script is defined in this project
 *
 * @property Utils $utils @required various helper functions
 * @property Shell $shell @required Helper for running commands in local shell
 * @property Git $git @required Git helper
 */
class Project extends Object_
{
    #region Properties
    public function __get($property) {
        /* @var Script $script */
        global $script;

        switch ($property) {
            case 'lock': return $this->lock = $this->getComposerLock();
            case 'packages': return $this->packages = $this->getPackages();
            case 'current': return $this->current = $script->path === $this->path;

            case 'utils': return $this->utils = $script->singleton(Utils::class);
            case 'shell': return $this->shell = $script->singleton(Shell::class);
            case 'git': return $this->git = $script->singleton(Git::class);
        }

        return null;
    }

    protected function getComposerLock() {
        return $this->utils->readJsonOrFail("{$this->path}/composer.lock");
    }

    protected function getPackages() {
        $result = [];

        foreach ($this->lock->packages ?? [] as $config) {
            $result[$config->name] = new Package(['project' => $this, 'lock' => $config]);
        }

        return $result;
    }
    #endregion

    public function verifyCurrent() {
        /* @var Script $script */
        global $script;

        if (!$this->current) {
            throw new Exception("Before running this command, change current directory to '$script->path'");
        }
    }

    public function verifyNoUncommittedChanges() {
        foreach ($this->packages as $package) {
            $this->verifyNoUncommittedChangesInDirectory($package->path);
        }
    }


    protected function verifyNoUncommittedChangesInDirectory($path) {
        if (!is_dir("{$path}/.git")) {
            return;
        }

        $this->shell->cd($path, function() use ($path) {
            // run a command which lists all uncommitted files and if it lists anything, stop
            if (!empty($this->git->getUncommittedFiles())) {
                throw new Exception("Commit and push pending changes in '{$path}' first");
            }

            // download missing commits from the server Git repo (if any)
            $this->git->fetch(true);

            // get the name of the current Git branch
            $branch = $this->git->getCurrentBranch();

            // count the number of Git commits local Git repo is behind (if $count is
            // positive) or ahead (if $count is negative).
            $count = $this->git->getPendingCommitCount();

            // if local and server Git repos are not the same, stop
            if ($count > 0) {
                throw new Exception("Push pending commits in '{$path}' first");
            }
            if ($count < 0) {
                throw new Exception("Pull pending commits in '{$path}' first");
            }
        }, true);
    }

    /**
     * Add package as a dependency to the project. For private packages, provide Git repo URL in
     * $repoUrl parameter
     *
     * @param $packageAndVersion
     * @param null $repoUrl
     * @throws Exception
     */
    public function require($packageAndVersion, $repoUrl = null) {
        if ($repoUrl) {
            if (($pos = strpos($packageAndVersion, ':')) !== false) {
                $package = substr($packageAndVersion, 0, $pos);
            }
            else {
                $package = $packageAndVersion;
            }

            $this->registerRepo($package, $repoUrl);
        }

        $this->shell->run("composer require {$packageAndVersion}");
    }

    protected function registerRepo($package, $repoUrl) {
        $name = strtr($package, '/-', '__');
        $this->shell->run("composer config repositories.{$name} vcs {$repoUrl}");
    }

    public function update() {
        $this->shell->run("composer update");
    }

    public function getPackage($name) {
        return $this->project->packages[$name] ?? new Package(['project' => $this, 'name' => $name]);
    }
}