<?php
    namespace Deploi\Modules\Archive;

    use Deploi\Modules\Base;
    use SplFileInfo;
    use RecursiveDirectoryIterator;
    use RecursiveIteratorIterator;
    use Exception;
    use ZipArchive;

    /**
     *  Archive Module
     *
     *  Accepts an array of paths and exclusions to turn into a ZIP archive
     *
     * @author Danny Kopping
     */
    class Archive extends Base
    {
        public $basePath;
        public $paths;
        public $excludePattern;

        public function register()
        {
            $this->hooks = array(
                "pre.zip",
                "post.zip"
            );
        }

        /**
         * Create an archive of files and folders
         *
         * @param $basePath
         * @param array $paths
         * @param array $excludePaths
         * @param null $excludePattern
         * @throws \Exception
         */
        public function __construct($basePath, $paths = array(), $excludePattern = null)
        {
            $this->basePath = realpath($this->basePath);
            $this->paths = $paths;
            $this->excludePattern = $excludePattern;

            if(empty($this->basePath))
                throw new Exception("Base path invalid for archive");

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

        public function save($location, $timestamp=true, $overwrite=false)
        {
            $location = realpath($location);
            if(empty($location))
                throw new Exception("Base path invalid for archive");

            $filename = "archive.zip";
            if($overwrite)
                $filename = "archive.zip";
            else if($timestamp)
                $filename = "archive".date("U").".zip";

            $path = is_file($location)
                    ? dirname($location).DIRECTORY_SEPARATOR.$filename
                    : $location.DIRECTORY_SEPARATOR.$filename;

            $zip = new ZipArchive();
            $zip->open($path, $overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE);

            $paths = array_unique($this->paths);
            foreach($paths as $path)
            {
                if(!$this->isExcluded($path, $this->excludePattern))
                {
                    if(is_dir($path))
                        continue;

                    $localPath = strpos($path, $this->basePath) === 0
                        ? substr($path, strlen($this->basePath))
                        : null;

                    $zip->addFile($path, $localPath);
                }
            }

            $zip->close();
        }

        /**
         * http://www.codedevelopr.com/recursively-scan-a-directory-using-php-spl-directoryiterator/
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
         * Determine whether a path is excluded due to an exclusion path or pattern
         *
         * @param $path
         * @param array $exclusions
         * @param null $exclusionPatterns
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
    }
