<?php
    namespace Deploi\Util\File;

    use Exception;
    use RecursiveIteratorIterator;
    use RecursiveDirectoryIterator;

    /**
     * This class allows you to represent a set of files and folders
     */
    class FileSet
    {
        private $basePath;
        private $paths;
        private $exclusions;

        public function __construct($basePath = null, $paths = null, $exclusions = null)
        {
            if(!empty($basePath)) 		$this->setBasePath($basePath);
            if(!empty($paths)) 			$this->setPaths($paths);
            if(!empty($exclusions)) 	$this->setExclusions($exclusions);
        }

        /**
         * @see http://www.codedevelopr.com/recursively-scan-a-directory-using-php-spl-directoryiterator/
         *
         * @param $path
         * @return array
         */
        public function recursiveFileFolderScan($path)
        {
            $directory = new RecursiveDirectoryIterator($path);
            $iterator = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::SELF_FIRST);

            foreach($iterator as $fileinfo)
            {
                $files[] = $fileinfo->getPathname();
            }

            return $files;
        }

        /**
         * Ensure that all paths are valid
         *
         * @param $basePath
         * @param $paths
         * @return array
         */
        private function normalizePaths($basePath, $paths)
        {
            $normalized = array();
            foreach($paths as $path)
            {
                $newPath = realpath($path == $basePath ? $path : $basePath . DIRECTORY_SEPARATOR . $path);
                if(empty($newPath))
                    $newPath = realpath($path);

                if(!empty($newPath))
                    $normalized[] = $newPath;
            }

            if(empty($normalized))
                return array();

            return array_unique($normalized);
        }

        /**
         * Determine whether a path is excluded due to an exclusion pattern
         *
         * @param $path
         * @param null $exclusionPatterns
         * @internal param array $exclusions
         * @return bool
         */
        private function isExcluded($path, $exclusionPatterns = null)
        {
            if(empty($exclusionPatterns))
                return false;

            if(!empty($exclusionPatterns))
            {
                // fix single arguments as strings
                if(is_string($exclusionPatterns))
                    $exclusionPatterns = array($exclusionPatterns);

                foreach($exclusionPatterns as $pattern)
                {
                    $matches = array();
                    preg_match("%$pattern%i", $path, $matches, PREG_NO_ERROR);

                    if(!empty($matches))
                        return true;
                }
            }

            return false;
        }


        /**
         * Get all paths excluded based on exclusion rules
         *
         * @param bool $excluded
         * @return array
         */
        public function getValidPaths($excluded = false)
        {
            $validPaths = array();

			$paths = $this->getPaths();
            if(empty($paths) || count($paths) <= 0)
                return array();

            $paths = array_unique($this->paths);
			if(!empty($paths) || count($paths) > 0)
			{
				foreach($paths as $path)
				{
					$localPath = strpos($path, $this->basePath) === 0
						? substr($path, strlen($this->basePath))
						: null;

					if(is_dir($path))
						continue;

					if(!$this->isExcluded($path, $this->exclusions))
					{
						if(!$excluded)
							$validPaths[] = array("path" => $path, "relative" => $localPath);
					} else
					{
						if($excluded)
							$validPaths[] = array("path" => $path, "relative" => $localPath);
					}
				}
			}

            return $validPaths;
        }

        /**
         * @return mixed
         */
        public function getBasePath()
        {
            return $this->basePath;
        }

        /**
         * @return mixed
         */
        public function getExclusions()
        {
            return $this->exclusions;
        }

        /**
         * @return mixed
         */
        public function getPaths()
        {
			$basePath = $this->getBasePath();
			if(!empty($basePath) && (empty($this->paths) || count($this->paths) == 0))
			{
				$this->paths = $this->normalizePaths($basePath, $this->recursiveFileFolderScan($basePath));
			}

            return $this->paths;
        }

        /**
         * @param $basePath
         * @throws \Exception
         */
        public function setBasePath($basePath)
        {
            $this->basePath = realpath($basePath);

            if(empty($this->basePath))
                throw new Exception("Base path invalid for fileset");
        }

        /**
         * @param $exclusions
         */
        public function setExclusions($exclusions)
        {
            $this->exclusions = $exclusions;
        }

        /**
         * @param $paths
         */
        public function setPaths($paths)
        {
            $this->paths = $paths;

            if(!empty($this->paths))
            {
                $this->paths = $this->normalizePaths($this->basePath, $this->paths);

                // get recursive scan of all provided paths
                $recursive = array();
                foreach($this->paths as $path)
                {
                    $files = $this->recursiveFileFolderScan($path);
                    if(!empty($files))
                    {
                        foreach($files as $file)
                            $recursive[] = $file;
                    }
                }

                if(!empty($recursive))
                    $this->paths = array_unique($recursive);
            }
        }
    }
