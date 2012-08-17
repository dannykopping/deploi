<?php
    namespace Deploi\Modules;

    /**
     *  Base module logic
     */
    class Base
    {
        public $name;
        public $version;
        public $description;

        public $hooks;

        /**
         * @override
         */
        public function register()
        {
            // initialize all hooks
        }
    }
