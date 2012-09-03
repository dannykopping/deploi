<?php
	namespace Deploi\Modules\SCMDeploy;

	/**
	 *	Deploy files from a Git repository
	 */
	use Deploi\Modules\Base;
	use Deploi\Modules\FileDeploy\Exception\Exception;
	use Deploi\Util\Config;
	use Deploi\Util\SCM\Git\Git;

	/**
	 *
	 */
	class GitDeploy extends Base
	{
		/**
		 * @var	Git
		 */
		private $git;

		/**
		 * @var	string
		 */
		private $targetDirectory;

		/**
		 *
		 */
		public function __construct()
		{
			parent::__construct();

			$this->git = new Git();
			$this->git->setRepository(Config::get("filedeploy.git.repository"));

			$this->targetDirectory = Config::get("filedeploy.git.targetDirectory");
			if(!realpath($this->targetDirectory))
			{
				@mkdir($this->targetDirectory, 0777, true);
				if(!realpath($this->targetDirectory))
				{
					throw new Exception(sprintf(Exception::PATH_CREATION_FAILURE, $this->targetDirectory, ""));
				}
			}
		}

		/**
		 * @return string
		 */
		public function getRepository()
		{
			return $this->git->getRepository();
		}

		/**
		 *
		 */
		protected function register()
		{
			parent::register();

			$this->name = "filedeploy.git";
			$this->name = "Deploy files from a Git repository";
		}

		/**
		 *
		 */
		public function getPayload()
		{
			if(!$this->gitRepoExists())
			{
				$this->git->cloneRepo()->setTargetPath($this->targetDirectory);
				$this->git->cloneRepo()->execute();
			}

			$this->git->setRepository($this->targetDirectory);
			$this->git->checkout()->setBranchname(Config::get("filedeploy.git.branchOrTag"));
			$this->git->checkout()->execute();
		}

		/**
		 * Tests to see if a .git folder exists
		 *
		 * @return bool
		 */
		private function gitRepoExists()
		{
			return count(glob($this->targetDirectory."/\.git")) !== 0;
		}
	}
