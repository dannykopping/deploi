<?php
	namespace Deploi\Modules\SchemaDeploy;

	use Deploi\Modules\Base;
	use Deploi\Modules\SchemaDeploy\lib\MySQLUtil\Tasks\SchemaDiff;
	use Deploi\Modules\SchemaDeploy\Exception\Exception;
	use Deploi\Util\Config;

	/**
	 *    Deploys a MySQL database schema
	 */
	class Deploy extends Base
	{
		/**
		 *
		 */
		public function __construct()
		{
			parent::__construct();

			list($sourceHost, $sourceSchema, $sourceUser, $sourcePass) = $this->getConfigInfo("source");
			list($targetHost, $targetSchema, $targetUser, $targetPass) = $this->getConfigInfo("target");

			$d = new SchemaDiff();
			$d->setLocal("$sourceSchema|$sourceUser:$sourcePass@$sourceHost");
			$d->setRemote("$targetSchema|$targetUser:$targetPass@$targetHost");
			$d->run();
		}

		/**
		 *
		 */
		protected function register()
		{
			parent::register();
		}

		private function getConfigInfo($name)
		{
			return array(Config::get("schemadeploy.{$name}Host"),
						Config::get("schemadeploy.{$name}Schema"),
						Config::get("schemadeploy.{$name}User"),
						Config::get("schemadeploy.{$name}Pass"));
		}
	}
