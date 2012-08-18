<?php
	namespace Deploi\Modules\FileDeploy;

	include_once "lib/phpseclib/Net/SSH2.php";
	include_once "lib/phpseclib/Net/SFTP.php";

	/**
	 *    Deploys a FileSet to a server via SSH & SFTP
	 */
	use Deploi\Modules\Base;
	use Deploi\Modules\FileDeploy\Exception\Exception;
	use Net_SSH2;
	use Deploi\Util\Security\Credentials;
	use Deploi\Util\File\FileSet;

	/**
	 *
	 */
	class Deploy extends Base
	{
		/**
		 * @var \Deploi\Util\File\FileSet
		 */
		private $fileSet;
		/**
		 * @var \Deploi\Util\Security\Credentials
		 */
		private $credentials;

		/**
		 * @var Net_SSH2
		 */
		private $ssh;

		/**
		 *
		 */
		protected function register()
		{
			$this->name = "filedeploy";
			$this->description = "Deploys a FileSet to a server via SSH & SFTP";

			$this->hooks = array(
				"pre.filedeploy",
				"post.filedeploy"
			);
		}

		/**
		 * @param \Deploi\Util\File\FileSet $fileSet
		 * @param \Deploi\Util\Security\Credentials $credentials
		 */
		public function __construct(FileSet $fileSet, Credentials $credentials)
		{
			parent::__construct();

			$this->fileSet = $fileSet;
			$this->credentials = $credentials;

			set_error_handler(array($this, "errorHandler"), E_ALL);

			$this->initConnections();
			$this->connect();
			$this->setupDirectoryStructure();

			$this->disconnect();

			restore_error_handler();
		}

		/**
		 * Setup and initialize the SSH and SFTP objects
		 */
		private function initConnections()
		{
			$host = $this->credentials->getHost();
			$port = $this->credentials->getPort();

			if(empty($host))
				throw new Exception(sprintf(Exception::INVALID_HOST, $host));

			$this->ssh = new Net_SSH2($host, empty($port) ? 22 : $port);
			$this->ssh->setTimeout(15);
		}

		/**
		 * @throws Exception\Exception
		 */
		private function connect()
		{
			if(!$this->ssh->login($this->credentials->getUsername(), $this->credentials->getPassword()))
			{
				throw new Exception(sprintf(Exception::CONNECTION_FAILURE, $this->credentials->getHost()));
			}

			echo("You're in to " . $this->ssh->getServerIdentification()) . "\n";
		}

		/**
		 * Creates required directory structure
		 *
		 * @throws Exception
		 */
		private function setupDirectoryStructure()
		{
			$webroot = $this->credentials->getWebroot();

			if(empty($webroot) || !$this->pathExists($webroot))
				throw new Exception(sprintf(Exception::INVALID_WEBROOT, $webroot));

			$home = trim($this->getHomeDir())."/deploi";
			$this->createPaths(array($webroot, "$home/releases", "$home/shared"));
		}

		/**
		 * Execute a command and catch any errors that ensue
		 *
		 * @param $command
		 * @return String
		 * @throws Exception\Exception
		 */
		private function run($command)
		{
			$result = "";

			try
			{
				echo "Running $command\n";
				$result = $this->ssh->exec($command);
			}
			catch(\Exception $e)
			{
				$this->ssh->disconnect();
				throw new Exception(sprintf(Exception::COMMAND_FAILURE, $command, $e->getMessage())."\n".print_r($e, true),
								$e->getCode(), $e, $e->getFile(), $e->getLine(), $e->getPrevious());
			}

			return $result;
		}

		/**
		 * Creates paths that do not currently exist
		 *
		 * @param $paths
		 * @throws Exception\Exception
		 */
		private function createPaths($paths)
		{
			if(empty($paths) || count($paths) == 0)
				return;

			foreach($paths as $path)
			{
				$result = $this->run("mkdir -p $path");

				if(strpos($result, "Permission denied") !== false)
				{
					throw new Exception(sprintf(Exception::PATH_CREATION_FAILURE, $path, "Permission denied."));
				}
			}
		}

		/**
		 * Returns the home directory for the signed-in user
		 *
		 * @return String
		 */
		private function getHomeDir()
		{
			return $this->run("echo ~");
		}

		/**
		 * Determine whether a path exists
		 *
		 * @param $path
		 * @return bool
		 */
		private function pathExists($path)
		{
			return trim($this->run(sprintf("[ -d %s ] && echo 'true' || echo 'false'", $path))) == "true";
		}

		/**
		 * @param $code
		 * @param $message
		 * @param $file
		 * @param $line
		 * @param null $context
		 * @throws Exception\Exception
		 */
		public function errorHandler($code, $message, $file, $line, $context = null)
		{
			$connectionFailure = "Cannot connect to %s. Error 60. Operation timed out";

			if($message == sprintf($connectionFailure, $this->credentials->getHost()))
				throw new Exception(sprintf(Exception::CONNECTION_FAILURE, $this->credentials->getHost()));
			else
			{
				print_r(func_get_args());
			}
		}

		/**
		 * Disconnects all current connections
		 */
		private function disconnect()
		{
			$this->ssh->disconnect();
		}
	}
