<?php

    namespace Deploi\Util;

    use SplFileInfo;
    use RecursiveIteratorIterator;
    use RecursiveDirectoryIterator;

    /**
     *
     */
    class Config
    {
        private static $configPath;
        private static $configVars;

        public static function setConfigPath($path, $scopes = array())
        {
            if(!realpath($path))
            {
                $error = sprintf(\Deploi\Exception\Config::INVALID_CONFIG_PATH, $path);
                throw new \Deploi\Exception\Config($error);
                return;
            }

            self::$configPath = realpath($path);
        }

        /**
         * Return a configuration variable
         *
         * @static
         * @param $var
         * @param string $scopeLimit        Limits scope of configuration variable search
         * @return string|null
         */
        public static function get($var, $scopeLimit = null, $refresh=false)
        {
            if(empty(self::$configVars) || $refresh)
                self::$configVars = self::getConfigurationVariables();


            if(empty(self::$configVars))
                return null;


            foreach(self::$configVars as $scope => $scopeVars)
            {
                if(empty($scopeVars))
                    continue;

                // if the scope is limited and the scope in this loop does not match the scope limit, skip
                if(!empty($scopeLimit) && trim(strtolower($scope)) != trim(strtolower($scopeLimit)))
                    continue;

                foreach($scopeVars as $key => $value)
                {
                    if(trim(strtolower($key)) == trim(strtolower($var)))
                        return $value;
                }
            }

            return null;
        }

        /**
         * Returns all configuration values in one associative array
         * with the configuration filenames as keys
         *
         * @static
         * @return array
         */
        private static function getConfigurationVariables()
        {
            $conf = array();

            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(self::$configPath),
                RecursiveIteratorIterator::CHILD_FIRST);

            foreach($iterator as $path => $file)
            {
                $ext = pathinfo($file->getFilename(), PATHINFO_EXTENSION);
                $filename = $file->getBasename(".$ext");

                if(empty($ext) || empty($filename))
                    continue;

                // if the file is a .conf file
                if($ext == "conf")
                {
                    $conf[$filename] = @parse_ini_file($file->getRealpath());
                }

            }

            return $conf;
        }
    }