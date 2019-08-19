<?php

namespace OsmScripts\Core;

/**
 * Git helper, works with current directory
 *
 * @property Shell $shell @required Helper for running commands in local shell
 */
class Git extends Object_
{
    #region Properties
    public function __get($property) {
        /* @var Script $script */
        global $script;

        switch ($property) {
            case 'shell': return $this->shell = $script->singleton(Shell::class);
        }

        return null;
    }
    #endregion

    public function init() {
        // create Git repository
        $this->shell->run('git init');

        // mark all files in current directory as tracked by Git, uncommitted new files
        $this->shell->run('git add .');

        // create first Git commit
        $this->shell->run('git commit -am "Initial commit"');
    }

    public function setOrigin($url) {
        $this->shell->run("git remote add origin {$url}");
    }

    public function push() {
        $this->shell->run('git push -u origin master');
    }

    public function getUncommittedFiles() {
        $this->shell->run('git update-index -q --refresh', true);

        return $this->shell->output('git diff-index --name-only HEAD --');
    }

    public function fetch($quiet = false) {
        $this->shell->run('git fetch', $quiet);
    }

    public function getCurrentBranch() {
        return implode($this->shell->output('git rev-parse --abbrev-ref HEAD'));
    }

    /**
     * Returns number of Git commits local Git repo is behind (if result is positive
     * number) or ahead (if result is negative number)
     *
     * @return int
     */
    public function getPendingCommitCount() {
        $branch = $this->getCurrentBranch();

        return intval(implode($this->shell->output(
            "git rev-list {$branch}...origin/{$branch} --ignore-submodules --count")));

    }

    public function commit($message) {
        // mark all files in current directory as tracked by Git, uncommitted new files
        $this->shell->run('git add .');

        // create first Git commit
        $this->shell->run("git commit -am \"{$message}\"");
    }
}