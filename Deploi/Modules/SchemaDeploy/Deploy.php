<?php
	namespace Deploi\Modules\SchemaDeploy;

	use Deploi\Modules\Base;
	use PDO;
	use PDOException;
	use Deploi\Modules\SchemaDeploy\Exception\Exception;
	use Deploi\Util\Config;

	/**
	 *    Deploys a MySQL database schema
	 */
	class Deploy extends Base
	{
		/**
		 * @var
		 */
		private $mysqldumpBin;

		/**
		 * @var	PDO
		 */
		private $sourceConn;

		/**
		 * @var	PDO
		 */
		private $targetConn;

		/**
		 *
		 */
		public function __construct()
		{
			parent::__construct();

			$this->validate();
			$this->compareAndDeploy();
		}

		/**
		 *
		 */
		protected function register()
		{
			parent::register();
		}

		/**
		 * @throws Exception
		 */
		private function validate()
		{
			$mysqldump = Config::get("schemadeploy.mysqldump");

			if(file_exists($mysqldump))
			{
				$this->mysqldumpBin = realpath($mysqldump);
			}
			else
			{
				throw new Exception(sprintf(Exception::BINARY_NOT_FOUND, $mysqldump));
			}

			$sourceDSN = "mysql:host".Config::get('schemadeploy.sourceHost').";dbName=". Config::get('schemadeploy.sourceSchema');
			$targetDSN = "mysql:host=".Config::get('schemadeploy.targetHost').";dbName=". Config::get('schemadeploy.targetSchema');

			$this->sourceConn = $this->getConnection($sourceDSN,
				Config::get("schemadeploy.sourceUser"), Config::get("schemadeploy.sourcePass"));

			$this->targetConn = $this->getConnection($targetDSN,
				Config::get("schemadeploy.targetUser"), Config::get("schemadeploy.targetPass"));
		}

		/**
		 * @param $dsn
		 * @param $user
		 * @param $pass
		 *
		 * @return PDO
		 * @throws Exception
		 */
		private function getConnection($dsn, $user, $pass)
		{
			try
			{
				$conn = new PDO($dsn, $user, $pass, array(PDO::ATTR_PERSISTENT => false));
			}
			catch(PDOException $e)
			{
				throw new Exception(sprintf(Exception::CANNOT_CONNECT, $dsn, $user));
			}

			return $conn;
		}

		private function compareAndDeploy()
		{
			list($sourceHost, $sourceSchema, $sourceUser, $sourcePass) = $this->getConfigInfo("source");
			list($targetHost, $targetSchema, $targetUser, $targetPass) = $this->getConfigInfo("target");

			$sourceXML = $this->getMySQLDumpXML($sourceUser, $sourcePass, $sourceHost, $sourceSchema);
			$targetXML = $this->getMySQLDumpXML($targetUser, $targetPass, $targetHost, $targetSchema, false);

			$sqldiff = __DIR__.DIRECTORY_SEPARATOR."lib".DIRECTORY_SEPARATOR."sqldiff".DIRECTORY_SEPARATOR."sqldiff.php";
			$sql = `$sqldiff --only-sql --user $sourceUser --pass $sourcePass --host $sourceHost --schema $sourceSchema $sourceXML $targetXML`;

			echo $sql;

			$sql = "USE `deploi-test`;".$sql;

			$statements = explode(";", $sql);
			foreach($statements as $statement)
			{
				$statement = trim($statement);

				if(empty($statement))
					continue;

				echo $statement.";\n";

				$this->targetConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$query = $this->targetConn->exec($statement.";");
			}

			@unlink($sourceXML);
			@unlink($targetXML);
		}

		private function getConfigInfo($name)
		{
			return array(Config::get("schemadeploy.{$name}Host"),
						Config::get("schemadeploy.{$name}Schema"),
						Config::get("schemadeploy.{$name}User"),
						Config::get("schemadeploy.{$name}Pass"));
		}

		private function getMySQLDumpXML($user, $pass, $host, $schema, $source=true)
		{
			$mysqldump = $this->mysqldumpBin;
			$tmp = sys_get_temp_dir().DIRECTORY_SEPARATOR.($source ? "source" : "target").".xml";

			`$mysqldump -X -d --user=$user --password=$pass --host=$host $schema > $tmp`;
			return $tmp;
		}
	}
