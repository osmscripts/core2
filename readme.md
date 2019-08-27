`osmscripts/core` Composer package is a library of useful classes for fast development of shell commands.

## Installation ##

Normally you don't have to install this package manually, as this package comes with every package created with `osmscripts create:package` command. 

However you can do it if needed by using the following commands (add `-g` switch to add the package to global Composer installation):

	composer config repositories.osmscripts_core vcs git@github.com:osmscripts/core.git
	composer require osmscripts/core

## Usage ##

All the API is divided into several helper classes. In order to use a helper class:

* define `@property` in your class comment for holding a reference to instance of the helper class;
* add `case` in `__get()` method of your class which retrieves helper class instance;
* in other methods of your class, use methods of the helper class instance.

See also examples below.

### `Files` - Working With Files ###

#### Generating Files ####

	<?php	

	use OsmScripts\Core\Object_;
	use OsmScripts\Core\Files;

	/**
	 * @property Files $files @required Helper for generating files.
	 */
	class YourClass extends Object_ {
	    #region Properties
	    public function __get($property) {
	        /* @var Script $script */
	        global $script;
	
	        switch ($property) {
	            case 'files': return $this->files = $script->singleton(Files::class);
	        }
	
	        return null;
	    }
	    #endregion

		public function yourMethod() {
			// render a file using a {template} file located in 
			// {package_dir}/templates/{script}/{template}.php. 
			//
			// See 'templates' directory of 'osmscripts/osmscripts' package 
			// for template file examples.
			// 
			// Specified variables (var1, var2) are passed to the template as
			// PHP variables $var1, $var2.
			// 
			// saves the result as {filename}, typicaly current directory-related 
			// file path is used. Directory structure is created if needed.  
	        $this->files->save('{filename}', $this->files->render('{template}', [
				'var1' => '{var1}',
				'var2' => '{var2}',
			]));
		}
	}

#### Updating Files ####

You may also load existing file, modify it and then save it back to disk using

    $this->files->save('{filename}', '{contents}');

#### Creating And Removing Symbolic Links ####

You may create and remove symbolic links:

    $this->files->createLink('{target_path}', '{link_path}');
    $this->files->deleteLink('{link_path}');

### `Shell` - Running Shell Commands ###

	<?php	

	use OsmScripts\Core\Object_;
	use OsmScripts\Core\Shell;

	/**
	 * @property Shell $shell @required Helper for running commands in local shell
	 */
	class YourClass extends Object_ {
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

		public function yourMethod() {
			// use run() method to run a {command} in current directory
			$this->shell->run('{command}');

			// use output() method to a {command} in current directory and 
			// fetch its output as array of strings
			$output = $this->shell->output('{command}');

			// by default, all commands execute in current directory
			// use cd() to temporarily change current directory 
			$this->shell->cd('{path}', function() {
				// all commands here are executed in specified {path}
			});	
		}
	}

### `Utils` - Various Helpers ###

	<?php	

	use OsmScripts\Core\Object_;
	use OsmScripts\Core\Utils;

	/**
	 * @property Utils $utils @required various helper functions
	 */
	class YourClass extends Object_ {
	    #region Properties
	    public function __get($property) {
	        /* @var Script $script */
	        global $script;
	
	        switch ($property) {
	            case 'utils': return $this->utils = $script->singleton(Utils::class);
	        }
	
	        return null;
	    }
	    #endregion

		public function yourMethod() {
			// read JSON file into plain PHP object. The result is null 
			// if there is no such file
			$json = $this->utils->readJson('{filename}');

			// read JSON file and fail if it can be read
			$json = $this->utils->readJsonOrFail('{filename}');

			// merge several complex array/object structures into one
			$mergedConfig = $this->utils->merge($config1, $config2);
		}
	}

### `Git` - Git Helper Functions ###

	<?php	

	use OsmScripts\Core\Object_;
	use OsmScripts\Core\Git;

	/**
	 * @property Git $git Git helper
	 */
	class YourClass extends Object_ {
	    #region Properties
	    public function __get($property) {
	        /* @var Script $script */
	        global $script;
	
	        switch ($property) {
	            case 'git': return $this->git = $script->singleton(Git::class);
	        }
	
	        return null;
	    }
	    #endregion

		public function yourMethod() {
			// put current directory under Git
            $this->git->init();

			// link with server repository 
            $this->git->setOrigin('{repo_url}');

			// push local changes to server
            $this->git->push();

			// create new Git commit with all pending new and modified files
            $this->git->commit("{commit_message}");

			// download server commits (but don't merge them yet)
            $this->git->fetch();

			// get useful information
			$branch = $this->git->getCurrentBranch();
			$files = $this->git->getUncommittedFiles();

			// positive if server is ahead, negative is local is ahead
			$count = $this->git->getPendingCommitCount();
		}
	}

### `Project` And `Package` - Crawling Composer Data ###

	<?php	

	use OsmScripts\Core\Object_;
	use OsmScripts\Core\Project;

	/**
	 * @property Project $project @required
	 */
	class YourClass extends Object_
	{
	    #region Properties
	    public function __get($property) {
	        /* @var Script $script */
	        global $script;
	
	        switch ($property) {
	            case 'project': return $this->project = new Project(['path' => $script->cwd]);
	        }
	
	        return null;
	    }
	    #endregion
	
		public function yourMethod() {
			if ($this->project->current) {
				// true if project is the one in which currently executed 
				// script is defined
			}
	
			// list all installed Composer packages
	        foreach ($this->project->packages as $package) {
				// package name
	            $name = $package->name;
				
				// last installed composer.json file
				$json = $package->lock;

				// current composer.json file
				$json = $package->json;
					
				// path of the package, relative to project directory
				$path = $package->path;

				// root PHP namespace of the package. By convention, it is
				// located in autoload.psr-4 section of composer.json file and 
				// it should resolve to src directory of the package 
				$namespace = $package->namespace;
	        }
			
			// script fails if verification does not pass
			$this->project->verifyCurrent();
			$this->project->verifyNoUncommittedChanges();

			// run composer require
			$this->project->require('{package}');

			// run composer update
			$this->project->update();
			
		}

## Architecture ##

This library internally uses [lazy properties](https://osmianski.com/2019/08/faster-lazy-properties-in-php.html) and encourages all dependent packages to use them too.
 
Global `$script` variable:
 
* contains information about currently executed script;
* serves as top-level object container for singleton objects, see `singleton()` method. 

JSON files are read as plain PHP objects. Hint classes are used for better IDE support on internal structure of JSON objects.

Packages define their configuration in their `composer.json` files. This configuration is merged and available in `$script->config`.

Script commands which modify files in target project should do all necessary checks to prevent possible data loss.

## Contributing ##

Install development branch:

	composer -g config repositories.osmscripts_core vcs git@github.com:osmscripts/core.git
	composer -g require osmscripts/core:dev-master@dev

## License And Credits ##

Copyright (C) 2019 - present UAB "Softnova".

All files of this package are licensed under [GPL-3.0](/LICENSE).
