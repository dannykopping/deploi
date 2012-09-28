<?php
	namespace Deploi\Modules\SchemaDeploy\lib\MySQLUtil\Tasks;

	use PDO;
	use PDOException;
	use Exception;
	use Deploi\Modules\SchemaDeploy\lib\MySQLUtil\Util\Server;
	use Deploi\Modules\SchemaDeploy\lib\MySQLUtil\Util\MySQLDiffHelper;

	require_once(dirname(__FILE__) . "/../Util/MySQLDiffHelper.php");
	require_once(dirname(__FILE__) . "/../Util/MySQLDiffOutputParser.php");

	class SchemaDiff
	{
		/**
		 * @var Server      Server 1 DSN
		 */
		private $local = null;

		/**
		 * @var Server      Server 2 DSN
		 */
		private $remote = null;

		private $format = "schema|username:password@host:port";

		private $tables = null;

		public function run()
		{
			if(empty($this->local) || empty($this->remote))
				throw new Exception("server1 and server2 both need to be set");

			$tables = $this->getTables();
			MySQLDiffHelper::getDiff($this->local, $this->remote, empty($tables) ? null : $tables);
		}

		private function getTablesForServer(Server $server)
		{
			if(empty($server))
				return null;

			$conn = $server->getConnection();
			try
			{
				$tables = $server->execute("SHOW TABLES FROM `" . $server->schema . "`", PDO::FETCH_COLUMN, false);
			}
			catch(PDOException $e)
			{
				throw $e;
			}

			return $tables;
		}

		/**
		 * Parse a DSN
		 * Format: (user:password
		 *
		 * @host:port)
		 *
		 * @param $dsn
		 *
		 * @return Server
		 */
		private function parseDSN($dsn)
		{
			if(empty($dsn))
				throw new Exception("Empty DSN");

			$pieces = explode("|", $dsn);
			if(empty($pieces) || count($pieces) < 2)
				throw new Exception("Invalid DSN. Correct format is " . $this->format);

			$schema = trim($pieces[0]);

			// split by @, first piece is credentials, second is host details
			$pieces = explode("@", trim($pieces[1]));
			if(empty($pieces) || count($pieces) < 2)
				throw new Exception("Invalid DSN. Correct format is " . $this->format);

			$credentials = $pieces[0];
			$host        = $pieces[1];

			$dsn = new Server();

			// split credentials by :
			$credentials = explode(":", $credentials);
			if(empty($credentials) || count($credentials) < 2)
				throw new Exception("Invalid DSN. Correct format is " . $this->format);

			// split host by :
			$host = explode(":", $host);
			if(empty($host) || count($host) < 1)
				throw new Exception("Invalid DSN. Correct format is " . $this->format);

			$dsn->schema   = $schema;
			$dsn->username = trim($credentials[0]);
			$dsn->password = trim($credentials[1]);
			$dsn->host     = trim($host[0]);
			if(isset($host[1]))
				$dsn->port = (int) trim($host[1]);

			return $dsn;
		}

		/**
		 * @param string $server1
		 */
		public function setLocal($server1)
		{
			$this->local         = $this->parseDSN($server1);
			$this->local->tables = $this->getTablesForServer($this->local);
		}

		/**
		 * @return string
		 */
		public function getLocal()
		{
			return $this->local;
		}

		/**
		 * @param string $server2
		 */
		public function setRemote($server2)
		{
			$this->remote         = $this->parseDSN($server2);
			$this->remote->tables = $this->getTablesForServer($this->remote);
		}

		/**
		 * @return string
		 */
		public function getRemote()
		{
			return $this->remote;
		}

		public function setTables($tables)
		{
			if(empty($tables))
			{
				$this->tables = null;

				return;
			}

			$tables = explode(",", $tables);
			foreach($tables as &$table)
				$table = trim($table);

			$this->tables = $tables;
		}

		/**
		 * @return array
		 */
		public function getTables()
		{
			return $this->tables;
		}
	}