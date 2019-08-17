<?php

namespace OsmScripts\Core\Commands;

use OsmScripts\Core\Command;
use OsmScripts\Core\Files;
use OsmScripts\Core\Git;
use OsmScripts\Core\Project;
use OsmScripts\Core\Script;
use OsmScripts\Core\Shell;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/** @noinspection PhpUnused */

/**
 * Base class for package creation commands. In overridden class, change `expect_current` property
 * if needed, define file creation logic in `createPackage()` method and call parent method,
 * add description and help in `configure()` method.
 *
 * @property Files $files @required Helper for generating files.
 * @property Shell $shell @required Helper for running commands in local shell
 * @property Project $project Information about Composer project in current working directory
 * @property Git $git Git helper
 * @property string $script_path @required Directory of the Composer project containing currently executed script
 * @property string $script_name @required Name of currently executed script
 *
 * @property string $package @required Name of package to be created
 * @property string $namespace @required PHP root namespace of the package
 * @property string $repo_url @required URL of the server Git repo
 * @property bool $no_update @required If set, skips creation and push of Git repo and Composer update
 * @property string $path @required Path to directory in `vendor` where new package is created
 */
abstract class CreatePackage extends Command
{
    public $expect_current = false;

    #region Properties
    public function __get($property) {
        /* @var Script $script */
        global $script;

        switch ($property) {
            // dependencies
            case 'files': return $this->files = $script->singleton(Files::class);
            case 'shell': return $this->shell = $script->singleton(Shell::class);
            case 'project': return $this->project = new Project(['path' => $script->cwd]);
            case 'git': return $this->git = $script->singleton(Git::class);
            case 'script_path': return $this->script_path = $script->path;
            case 'script_name': return $this->script_name = $script->name;

            // arguments and options
            case 'package': return $this->package = $this->input->getArgument('package');
            case 'namespace': return $this->namespace = $this->getNamespace();
            case 'repo_url': return $this->repo_url = $this->getRepoUrl();
            case 'no_update': return $this->no_update = $this->input->getOption('no-update');

            // calculated properties
            case 'path': return $this->path = "vendor/{$this->package}";
        }

        return null;
    }

    protected function getNamespace() {
        if (!($result = $this->input->getOption('namespace'))) {
            $result = implode('\\', array_map('ucfirst', explode('/', $this->package)));
        }

        if (strrpos($result, '\\') !== strlen($result) - strlen('\\')) {
            $result .= '\\';
        }

        return $result;
    }

    protected function getRepoUrl() {
        return $this->input->getOption('repo_url') ?: "git@github.com:{$this->package}.git";
    }
    #endregion

    protected function configure() {
        $this
            ->addArgument('package', InputArgument::REQUIRED,
                "Name of Composer package to be created")
            ->addOption('namespace', null, InputOption::VALUE_OPTIONAL,
                "Root namespace of PHP classes in this package, use '\\' delimiter. " .
                "If omitted, inferred from package name")
            ->addOption('repo_url', null, InputOption::VALUE_OPTIONAL,
                "URL of EMPTY server Git repo for newly created package. " .
                "If omitted, GitHub repo with package's name is assumed")
            ->addOption('no-update', null, InputOption::VALUE_NONE,
                "Skip creation and push of Git repo and Composer update");
    }

    protected function handle() {
        if ($this->expect_current) {
            // this command is expected to run from the global Composer installation and it is expected
            // to generate files in the the global Composer installation
            $this->project->verifyCurrent();
        }

        if (!$this->no_update) {
            // in the end, this command runs `composer update` which overwrites files in project's `vendor`
            // directory, so all the files in `vendor` directory are expected to be committed to their Git repos
            // and pushed to server
            $this->project->verifyNoUncommittedChanges();
        }

        // create a directory for new Composer package in `vendor` directory and
        // `composer.json` file in it which defines the directory as valid Composer package
        $this->createPackage();

        if (!$this->no_update) {
            // put package files under Git and push them to repo on server
            $this->shell->cd($this->path, function() {
                $this->git->init();
                $this->git->setOrigin($this->repo_url);
                $this->git->push();
            });

            // Register newly created package with Composer
            //
            // Package PHP namespace will be resolved to `src` subdirectory so all PHP classes
            // in `src` subdirectory will be autoloaded.
            $this->project->require("{$this->package}:dev-master@dev", $this->repo_url);
        }

        $this->shell->run("{$this->script_name} var package={$this->package}");
    }

    abstract protected function createPackage();
}