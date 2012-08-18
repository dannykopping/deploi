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

		public function __construct()
		{
			$this->register();
		}

        /**
         * @override
         */
        protected function register()
        {
            // initialize all hooks
        }
    }
