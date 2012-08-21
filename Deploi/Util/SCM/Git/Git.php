<?php

	namespace Deploi\Util\SCM\Git;

	/**
	 * Copyright 2010 Kousuke Ebihara
	 *
	 * Licensed under the Apache License, Version 2.0 (the "License");
	 * you may not use this file except in compliance with the License.
	 * You may obtain a copy of the License at
	 *
	 * http://www.apache.org/licenses/LICENSE-2.0
	 *
	 * Unless required by applicable law or agreed to in writing, software
	 * distributed under the License is distributed on an "AS IS" BASIS,
	 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
	 * See the License for the specific language governing permissions and
	 * limitations under the License.
	 *
	 * PHP Version 5
	 *
	 * @category  VersionControl
	 * @package   Git
	 * @author    Kousuke Ebihara <ebihara@php.net>
	 * @copyright 2010 Kousuke Ebihara
	 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
	 */

	/**
	 * The OO interface for Git
	 *
	 * An instance of this class can be handled as OO interface for a Git repository.
	 *
	 * @author    Kousuke Ebihara <ebihara@php.net>
	 * @copyright 2010 Kousuke Ebihara
	 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
	 */
	use Deploi\Util\SCM\Git\Util\Command;
	use Deploi\Util\SCM\Git\Task\Tag;
	use Deploi\Util\SCM\Git\Task\Push;
	use Deploi\Util\SCM\Git\Task\Pull;
	use Deploi\Util\SCM\Git\Task\Merge;
	use Deploi\Util\SCM\Git\Task\Log;
	use Deploi\Util\SCM\Git\Task\InitRepo;
	use Deploi\Util\SCM\Git\Task\GarbageCollect;
	use Deploi\Util\SCM\Git\Task\Fetch;
	use Deploi\Util\SCM\Git\Task\Commit;
	use Deploi\Util\SCM\Git\Task\CloneRepo;
	use Deploi\Util\SCM\Git\Task\Checkout;
	use Deploi\Util\SCM\Git\Task\Branch;
	use Deploi\Util\SCM\Git\Internal\Exception;
	use Deploi\Util\SCM\Git\Object\Tree;
	use Deploi\Util\SCM\Git\Util\RevListFetcher;

	/**
	 *
	 */
	class Git
	{
		/**
		 * The directory for this repository
		 *
		 * @var string
		 */
		protected $directory;

		/**
		 * Location to git binary
		 *
		 * @var string
		 */
		protected $gitCommandPath = '/usr/bin/git';

		/**
		 * Current repository directory
		 *
		 * @var string
		 */
		private $repository;

		/**
		 * @var	Branch
		 */
		private $branch;
		/**
		 * @var Checkout
		 */
		private $checkout;
		/**
		 * @var CloneRepo
		 */
		private $cloneRepo;
		/**
		 * @var Commit
		 */
		private $commit;
		/**
		 * @var Fetch
		 */
		private $fetch;
		/**
		 * @var GarbageCollect
		 */
		private $gc;
		/**
		 * @var InitRepo
		 */
		private $initRepo;
		/**
		 * @var	Log
		 */
		private $log;
		/**
		 * @var	Merge
		 */
		private $merge;
		/**
		 * @var Pull
		 */
		private $pull;
		/**
		 * @var Push
		 */
		private $push;
		/**
		 * @var Tag
		 */
		private $tag;

		/**
		 * Constructor
		 *
		 * @param string $reposDir A directory path to a git repository
		 *
		 * @throws Exception
		 */
		public function __construct($reposDir = './')
		{
			if(!is_dir($reposDir))
			{
				$message = 'You must specified readable directory as repository.';
				throw new Exception($message);
			}

			$this->directory = $reposDir;
		}

		/**
		 * Get Git version (e.g. 1.7.0)
		 *
		 * @return string
		 */
		public function getGitVersion()
		{
			$command = $this->getCommand('--version');

			return substr(trim($command->execute()), strlen('git version '));
		}

		/**
		 * Set repository directory
		 *
		 * @param string $repository Repo directory
		 */
		public function setRepository($repository)
		{
			$this->repository = $repository;
		}

		/**
		 * Get repository directory
		 *
		 * @return string
		 */
		public function getRepository()
		{
			return $this->repository;
		}

		/**
		 * Get an instance of the RevListFetcher that is
		 * related to this repository
		 *
		 * @return RevListFetcher
		 */
		public function getRevListFetcher()
		{
			return new RevListFetcher($this);
		}

		/**
		 * Get an array of the Commit objects
		 *
		 * @param mixed $object     The commit object. It can be string
		 *                          or an instance of the Object
		 * @param int   $maxResults A number of results
		 * @param int   $offset     A starting position of results
		 *
		 * @return RevListFetcher
		 */
		public function getCommits($object = 'master', $maxResults = 100, $offset = 0)
		{
			return $this->getRevListFetcher()
				->target((string) $object)
				->addDoubleDash(true)
				->setOption('max-count', $maxResults)
				->setOption('skip', $offset)
				->fetch();
		}

		/**
		 * Create a new clone from the specified repository
		 * It is wrapper of "git clone" command.
		 *
		 * @param string $repository The path to repository
		 * @param bool   $isBare     Whether to create bare clone
		 * @param string $directory  The path to new repository
		 *
		 * @return null
		 */
		public function createClone($repository, $isBare = false, $directory = null)
		{
			$command = $this->getCommand('clone')
				->setOption('bare', $isBare)
				->setOption('q')
				->addArgument($repository);

			if(null !== $directory)
			{
				$command->addArgument($directory);

				// cloning to empty directory is supported in 1.6.2-rc0 +
				// see: http://git.kernel.org/?p=git/git.git;a=commit;h=55892d239819
				if(is_dir($directory) && version_compare('1.6.1.4', $this->getGitVersion(), '>='))
				{
					$isEmptyDir = true;
					$entries    = scandir($directory);
					foreach($entries as $entry)
					{
						if('.' !== $entry && '..' !== $entry)
						{
							$isEmptyDir = false;

							break;
						}
					}

					if($isEmptyDir)
					{
						@rmdir($directory);
					}
				}
			}
			$command->execute();

			$this->directory = $directory;
		}

		/**
		 * Initialize a new repository
		 *
		 * It is wrapper of "git init" command.
		 *
		 * @param bool $isBare Whether to create bare clone
		 *
		 * @return null
		 */
		public function initRepository($isBare = false)
		{
			if(!$isBare || version_compare('1.5.6.6', $this->getGitVersion(), '<='))
			{
				$this->getCommand('init')
					->setOption('bare', $isBare)
					->setOption('q')
					->execute();
			}
			else
			{
				// see: http://git.kernel.org/?p=git/git.git;a=commit;h=74d3b23
				$this->getCommand('--bare')
					->addArgument('init')
					->addArgument('-q') // it is just a quick hack
					->execute();
			}
		}

		/**
		 * Alias of Git::initRepository()
		 *
		 * This method is available for backward compatibility.
		 *
		 * @param bool $isBare Whether to create bare clone
		 *
		 * @return null
		 */
		public function initialRepository($isBare = false)
		{
			$this->initRepository($isBare);
		}

		/**
		 * Get an array of branch names
		 *
		 * @return array
		 */
		public function getBranches()
		{
			$result = array();

			$commandResult = explode(PHP_EOL,
				rtrim($this->getCommand('branch')->execute()));
			foreach($commandResult as $k => $v)
			{
				$result[$k] = substr($v, 2);
			}

			return $result;
		}

		/**
		 * Get an array of remote branch names
		 *
		 * @param string $name The name of remote repository
		 *
		 * @return array
		 */
		public function getRemoteBranches($name = 'origin')
		{
			$result = array();

			$commandResult = $this->getCommand('branch')
				->setOption('r')
				->execute();
			$commandResult = explode(PHP_EOL, rtrim($commandResult));

			foreach($commandResult as $v)
			{
				$v = trim($v);

				$prefix = $name . '/';
				if(0 !== strpos($v, $prefix))
				{
					continue;
				}

				$result[] = substr($v, strlen($prefix));
			}

			return $result;
		}

		/**
		 * Get a current branch name
		 *
		 * @return string
		 */
		public function getCurrentBranch()
		{
			$commandResult = $this->getCommand('symbolic-ref')
				->addArgument('HEAD')
				->execute();

			return substr(trim($commandResult), strlen('refs/heads/'));
		}

		/**
		 * Checkout the specified branch
		 *
		 * Checking out a path is not supported currently.
		 *
		 * @param mixed $object The commit object. It can be string
		 *                      or an instance of the Object
		 *
		 * @return null
		 */
		/*public function checkout($object)
		{
			$this->getCommand('checkout')
				->addDoubleDash(true)
				->setOption('q')
				->addArgument((string) $object)
				->execute();
		}*/

		/**
		 * Get an array of branch object names
		 *
		 * @return array
		 */
		public function getHeadCommits()
		{
			$result = array();

			$command = $this->getCommand('for-each-ref')
				->setOption('format', '%(refname),%(objectname)')
				->addArgument('refs/heads');

			$commandResult = explode(PHP_EOL, trim($command->execute()));
			foreach($commandResult as $v)
			{
				$pieces = explode(',', $v);
				if(2 == count($pieces))
				{
					$result[substr($pieces[0], strlen('refs/heads/'))] = $pieces[1];
				}
			}

			return $result;
		}

		/**
		 * Get an array of tag names
		 *
		 * @return array
		 */
		public function getTags()
		{
			$result = array();

			$command = $this->getCommand('for-each-ref')
				->setOption('format', '%(refname),%(objectname)')
				->addArgument('refs/tags');

			$commandResult = explode(PHP_EOL, trim($command->execute()));
			foreach($commandResult as $v)
			{
				$pieces = explode(',', $v);
				if(2 == count($pieces))
				{
					$result[substr($pieces[0], strlen('refs/tags/'))] = $pieces[1];
				}
			}

			return $result;
		}

		/**
		 * Get an instance of the Tree that is related
		 * to this repository
		 *
		 * @param mixed $object The commit object. It can be string
		 *                      or an instance of the Object
		 *
		 * @return Tree
		 */
		public function getTree($object)
		{
			return new Tree($this, (string) $object);
		}

		/**
		 * Get an instance of the Command that is related
		 * to this repository
		 *
		 * @param string $subCommand A subcommand to execute
		 *
		 * @return Command
		 */
		public function getCommand($subCommand)
		{
			$command = new Command($this);
			$command->setSubCommand($subCommand);

			return $command;
		}

		/**
		 * Get the directory for this repository
		 *
		 * @return string
		 */
		public function getDirectory()
		{
			return $this->directory;
		}

		/**
		 * Get the location to git binary
		 *
		 * @return string
		 */
		public function getGitCommandPath()
		{
			return $this->gitCommandPath;
		}

		/**
		 * Set the location to git binary
		 *
		 * @param string $path The location to git binary
		 *
		 * @return null
		 */
		public function setGitCommandPath($path)
		{
			$this->gitCommandPath = $path;
		}

		/**
		 *
		 */
		public function branch()
		{
			if(!$this->branch)		$this->branch = new Branch();
			if($this->repository)	$this->branch->setRepository($this->repository);
			return $this->branch;
		}

		/**
		 * @return Task\Checkout
		 */
		public function checkout()
		{
			if(!$this->checkout)		$this->checkout = new Checkout();
			if($this->repository)		$task->setRepository($this->repository);
			return $this->checkout;
		}

		/**
		 * @return Task\CloneRepo
		 */
		public function cloneRepo()
		{
			if(!$this->cloneRepo)	$this->cloneRepo = new CloneRepo();
			if($this->repository)	$this->cloneRepo->setRepository($this->repository);
			return $this->cloneRepo;
		}

		/**
		 * @return Task\Commit
		 */
		public function commit()
		{
			if(!$this->commit)		$this->commit = new Commit();
			if($this->repository)	$this->commit->setRepository($this->repository);
			return $this->commit;
		}

		/**
		 * @return Task\Fetch
		 */
		public function fetch()
		{
			if(!$this->fetch)		$this->fetch = new Fetch();
			if($this->repository)	$this->fetch->setRepository($this->repository);
			return $this->fetch;
		}

		/**
		 * @return Task\GarbageCollect
		 */
		public function gc()
		{
			if(!$this->gc)			$this->gc = new GarbageCollect();
			if($this->repository)	$this->gc->setRepository($this->repository);
			return $this->gc;
		}

		/**
		 * @return Task\InitRepo
		 */
		public function init()
		{
			if(!$this->initRepo)	$this->initRepo = new InitRepo();
			if($this->repository)	$this->initRepo->setRepository($this->repository);
			return $this->initRepo;
		}

		/**
		 * @return Task\Log
		 */
		public function log()
		{
			if(!$this->log)			$this->log = new Log();
			if($this->repository)	$this->log->setRepository($this->repository);
			return $this->log;
		}

		/**
		 * @return Task\Merge
		 */
		public function merge()
		{
			if(!$this->merge) 		$this->merge = new Merge();
			if($this->repository)	$this->merge->setRepository($this->repository);
			return $this->merge;
		}

		/**
		 * @return Task\Pull
		 */
		public function pull()
		{
			if(!$this->pull)		$this->pull = new Pull();
			if($this->repository)	$this->pull->setRepository($this->repository);
			return $this->pull;
		}

		/**
		 * @return Task\Push
		 */
		public function push()
		{
			if(!$this->push)		$this->push = new Push();
			if($this->repository)	$this->push->setRepository($this->repository);
			return $this->push;
		}

		/**
		 * @return Task\Tag
		 */
		public function tag()
		{
			if(!$this->tag)		$this->tag = new Tag();
			if($this->repository)	$this->tag->setRepository($this->repository);
			return $this->tag;
		}
	}
