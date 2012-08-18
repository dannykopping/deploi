<?php
	namespace Deploi\Modules\Archive;

	use Deploi\Modules\Base;
	use Deploi\Util\File\FileSet;
	use Phar;
	use PharData;
	use SplFileInfo;
	use RecursiveDirectoryIterator;
	use RecursiveIteratorIterator;
	use Exception;

	/**
	 *  Archive Module
	 *
	 *  Accepts an array of paths and exclusions to turn into an archive
	 *
	 * @author Danny Kopping
	 */
	class Archive extends Base
	{
		private $fileSet;

		public function register()
		{
			$this->name = "archive";
			$this->description = "Creates an archive for a set of files";

			$this->hooks = array(
				"pre.archive",
				"post.archive"
			);
		}

		/**
		 * Create an archive with a given FileSet
		 *
		 * @param $basePath
		 * @param array $paths
		 * @param array $excludePaths
		 * @param null $excludePattern
		 * @throws \Exception
		 */
		public function __construct(FileSet $fileSet)
		{
			parent::__construct();

			$this->fileSet = $fileSet;
		}

		/**
		 * Create a compress archive with the given FileSet
		 *
		 * @param $location
		 * @param bool $timestamp
		 * @param bool $overwrite
		 */
		public function save($location, $timestamp = true, $overwrite = false)
		{
			$path = $this->getFilename($location, $timestamp, $overwrite);

			$tar = new PharData($path);
			$validPaths = $this->fileSet->getValidPaths();

			if(!empty($validPaths) && count($validPaths) > 0)
			{
				foreach($validPaths as $path)
				{
					$tar->addFile($path["path"], $path["relative"]);
				}
			}

			try
			{
				$tar->convertToData(Phar::TAR, Phar::GZ);
			}
			catch(Exception $e)
			{
				// ignore this error
				if(strpos($e->getMessage(), "a phar with that name already exists") === false)
					print_r($e);
			}
		}

		private function getFilename($location, $timestamp = true, $overwrite = false)
		{
			$location = realpath($location);
			if(empty($location))
				throw new Exception("Base path invalid for archive");

			$filename = "archive.tar.gz";
			if($overwrite)
				$filename = "archive.tar.gz";
			else if($timestamp)
				$filename = "archive" . date("U") . ".tar.gz";

			return is_file($location)
				? dirname($location) . DIRECTORY_SEPARATOR . $filename
				: $location . DIRECTORY_SEPARATOR . $filename;
		}
	}
