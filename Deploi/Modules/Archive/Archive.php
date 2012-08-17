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
        public function archive($basePath, $paths = array(), $excludePaths = array(), $excludePattern = null)
        {
            $basePath = realpath($basePath);

            if(empty($basePath))
                throw new Exception("Base path invalid for archive");

            if(!empty($paths))
            {
                $paths = $this->normalizePaths($basePath, $paths);

                // get recursive scan of all provided paths
                $recursive = array();
                foreach($paths as $path)
                {
                    $files = $this->recursiveFileFolderScan($path);
                    if(!empty($files))
                    {
                        foreach($files as $file)
                            $recursive[] = $file;
                    }
                }

                if(!empty($recursive))
                    $paths = array_unique($recursive);
            }

            if(!empty($excludePaths))
                $excludePaths = $this->normalizePaths($basePath, $excludePaths);

            $zip = new ZipArchive();
            $zip->open(__DIR__."/test.zip", ZIPARCHIVE::OVERWRITE);

            $paths = array_unique($paths);
            foreach($paths as $path)
            {
                if(!$this->isExcluded($path, $excludePaths, $excludePattern))
                {
                    if(is_dir($path))
                        continue;

                    echo $path."\n";

                    $localPath = strpos($path, $basePath) === 0
                                ?   substr($path, strlen($basePath))
                                :   null;

                    $zip->addFile($path, $localPath);
                }
                else echo "Ex: $path\n";
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
        private function isExcluded($path, $exclusions = array(), $exclusionPatterns = null)
        {
            if(empty($exclusions) && empty($exclusionPatterns))
                return false;

            if(!empty($exclusions))
            {
                // fix single arguments as strings
                if(is_string($exclusions))
                    $exclusions = array($exclusions);

                foreach($exclusions as $exPath)
                {
                    $normalizedPath = trim(strtolower($path));
                    $normalizedExPath = trim(strtolower($exPath));

                    if($normalizedPath == $normalizedExPath)
                        return true;

                    // exclude path and all children
                    if(strpos($normalizedPath, $normalizedExPath) === 0)
                        return true;
                }
            }

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
