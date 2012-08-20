<?php
	namespace Deploi\Modules\FileDeploy;

	include_once "lib/phpseclib/Net/SSH2.php";
	include_once "lib/phpseclib/Net/SFTP.php";

	/**
	 *    Deploys a FileSet to a server via SSH & SFTP
	 */
	use Deploi\Modules\Base;
	use Deploi\Util\File\PathHelper;
	use ErrorException;
	use Deploi\Util\Config;
	use Net_SFTP;
	use Deploi\Modules\Archive\Archive;
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
		 * @var    Net_SFTP
		 */
		private $sftp;

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
		 * @param \Deploi\Util\File\FileSet         $fileSet
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
			$this->deployPayload();

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

			$this->sftp = new Net_SFTP($host, empty($port) ? 22 : $port);
			$this->sftp->setTimeout(15);
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

			if(!$this->sftp->login($this->credentials->getUsername(), $this->credentials->getPassword()))
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

			$home = trim($this->getHomeDir()) . "/deploi";
			$this->createPaths(array($webroot, "$home/releases", "$home/shared"));
		}

		/**
		 * Create an archive of all the files to be deployed
		 */
		private function deployPayload()
		{
			$payload = new Archive($this->fileSet);
			$tempFile = $payload->save(sys_get_temp_dir() . DIRECTORY_SEPARATOR . "deploi");

			$home = trim($this->getHomeDir());
			$webroot = $this->credentials->getWebroot();
			$filename = basename($tempFile);
			$filenameNoExt = substr($filename, 0, strpos($filename, "."));

			$releases = "$home/deploi/releases";
			$currentTimestamp = date("U");

			if(Config::get("filedeploy.useSymlinks"))
			{
				$this->sftp->chdir($releases);
				$this->sftp->put($filename, $tempFile, NET_SFTP_LOCAL_FILE);

				// if the webroot already exists and is not a symbolic link, move it and recreate webroot
				if($this->pathExists($webroot) && !$this->pathIs($webroot, PathHelper::IS_SYMBOLIC))
					$this->run(sprintf("mv %s %s.$currentTimestamp", $webroot, $webroot));

				$this->run("cd $releases");
				$this->createPaths(array("$releases/$filenameNoExt"));
				echo $this->run(sprintf("tar mvxf %s -C %s", "$releases/$filename", "$releases/$filenameNoExt"));
				echo $this->run(sprintf("ln -snvf %s %s", "$releases/$filenameNoExt", $webroot));

				echo $this->sftp->delete("$releases/$filename");
			}
			else
			{
				$backup = "$releases/backup-$currentTimestamp";
				if(!$this->checkDirectoryPerms($releases, PathHelper::IS_WRITABLE))
					throw new Exception(sprintf(Exception::PERMISSION_FAILURE, $releases, PathHelper::getText(PathHelper::IS_WRITABLE)));

				if(Config::get("filedeploy.tarballBackups"))
				{
					$backup .= ".tar.gz";

					echo $this->run(sprintf("tar cvzf %s %s", $backup, "$webroot"));

					if(!$this->pathExists($backup, true))
						throw new Exception(sprintf(Exception::FILE_CREATION_FAILURE, $backup, "Path $backup is invalid or " .
							"backup file could not be written"));
				}
				else
				{
					$this->createPaths(array($backup));
					echo $this->run(sprintf("cp --parents -R %s %s", $webroot, $backup));
				}

				// once backup is created, clear the webroot
				echo $this->clearDirectory($webroot);

				// upload new file
				$this->sftp->chdir($webroot);
				$this->sftp->put($filename, $tempFile, NET_SFTP_LOCAL_FILE);
				echo $this->run(sprintf("tar mvxf %s -C %s --overwrite", "$webroot/$filename", "$webroot"));

				echo $this->run("rm $webroot/$filename");
			}
		}

		/**
		 * Execute a command and catch any errors that ensue
		 *
		 * @param $command
		 *
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
				throw new Exception(sprintf(Exception::COMMAND_FAILURE, $command, $e->getMessage()) . "\n" . print_r($e, true),
					$e->getCode(), $e, $e->getFile(), $e->getLine(), $e->getPrevious());
			}

			return trim($result);
		}

		/**
		 * Creates paths that do not currently exist
		 *
		 * @param $paths
		 *
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
		 * @param $path
		 * @param $command
		 *
		 * @return bool
		 */
		private function pathIs($path, $command)
		{
			return $this->run(sprintf("[ %s %s ] && echo 'true' || echo 'false'", $command, $path)) == "true";
		}

		/**
		 * @param      $path
		 * @param bool $recursive
		 * @param bool $filesOnly
		 *
		 * @return String
		 */
		private function clearDirectory($path, $recursive = true, $filesOnly = false)
		{
			// if the directory does not exist, don't bother
			if(!$this->pathExists($path))
				return;

			$options = "";
			if($recursive) $options .= "r";
			if(!$filesOnly) $options .= "f";

			return $this->run(sprintf("rm -%s %s/*", $options, $path));
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
		 * @param      $path
		 * @param bool $file
		 *
		 * @return bool
		 */
		private function pathExists($path, $file = false)
		{
			return $this->run(sprintf("[ %s %s ] && echo 'true' || echo 'false'", $file ? "-f" : "-d", $path)) == "true";
		}

		/**
		 * @param $path
		 * @param $type
		 *
		 * @throws Exception\Exception
		 *
		 * @return bool
		 */
		private function checkDirectoryPerms($path, $type)
		{
			return $this->pathIs($path, $type);
		}

		/**
		 * @param      $code
		 * @param      $message
		 * @param      $file
		 * @param      $line
		 * @param null $context
		 *
		 * @throws Exception\Exception
		 * @throws ErrorException
		 * @return void
		 */
		public function errorHandler($code, $message, $file, $line, $context = null)
		{
			$host = $this->credentials->getHost();

			switch($message)
			{
				case sprintf("Cannot connect to %s. Error 60. Operation timed out", $host):
					throw new Exception(sprintf(Exception::CONNECTION_FAILURE, $host));
					break;
				case sprintf("Cannot connect to %s. Error 61. Connection refused", $host):
					throw new Exception(sprintf(Exception::CONNECTION_REFUSED, $host));
					break;
				default:
					if($code == E_ERROR || $code == E_USER_ERROR)
						throw new ErrorException($message, $code, null, $file, $line);
					break;
			}
		}

		/**
		 * Disconnects all current connections
		 */
		private function disconnect()
		{
			$this->ssh->disconnect();
			$this->sftp->disconnect();
		}
	}
