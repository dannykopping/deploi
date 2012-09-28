<?php
	namespace Deploi\Modules\SchemaDeploy\lib\MySQLUtil\Util;

	use PDO;
	use Deploi\Util\Config;
	use Exception;
	use PDOException;

	/**
	 *
	 */
	class Server
	{
		/**
		 * @var
		 */
		public $schema;
		/**
		 * @var
		 */
		public $username;
		/**
		 * @var
		 */
		public $password;
		/**
		 * @var
		 */
		public $host;
		/**
		 * @var int
		 */
		public $port = 3306;

		/**
		 * @var
		 */
		public $tables;

		/**
		 *
		 */
		const CANNOT_CONNECT = "Cannot connect to server with DSN '%s'";

		/**
		 * @return \PDO
		 * @throws \Exception
		 */
		public function getConnection()
		{
			$dsn = "mysql:host={$this->host};dbname={$this->schema}";

			try
			{
				$conn = new PDO($dsn, $this->username, $this->password,
					array(
						 PDO::ATTR_PERSISTENT => true
					));
			}
			catch(PDOException $e)
			{
				throw new Exception(sprintf(self::CANNOT_CONNECT, $dsn));
			}

			return $conn;
		}

		/**
		 * @param      $query
		 * @param int  $fetchMode
		 * @param bool $fetchAll
		 *
		 * @return array|null
		 */
		public function execute($query, $fetchMode = PDO::FETCH_ASSOC, $fetchAll = true, $disableFK = false, $transaction = false)
		{
			$query = trim($query);
			if(empty($query))
				return null;

			if(substr($query, strlen($query) - 2, 1) != ";")
				$query .= ";";

			if($disableFK)
				$query = Server::disableFKQuery($query);

			$query = trim($query);

			$queries = array();
			$split   = explode(";", $query);
			foreach($split as $query)
			{
				$query = trim($query);
				if(empty($query))
					continue;

				$queries[] = $query . ";";
			}

			if(count($queries) <= 0)
				return null;

			$this->getConnection()->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

			$results = array();
			foreach($queries as $query)
			{
				echo $query."\n";

				if($transaction)
				{
					$results[] = $this->runTransactionalQueries(array($query), $fetchMode);
				}
				else
				{
					$statement = $this->getConnection()->query($query);
					$ex        = $statement->execute();

					$results[] = $fetchAll ? $statement->fetchAll($fetchMode) : $statement->fetch($fetchMode);

					$statement->closeCursor();
					$statement = null;
				}
			}

			return $results;
		}

		/**
		 * @param array $queries
		 * @param int   $fetchMode
		 *
		 * @return array
		 */
		public function runTransactionalQueries(array $queries, $fetchMode = PDO::FETCH_ASSOC)
		{
			$results = array();
			$this->getConnection()->setAttribute(PDO::ATTR_AUTOCOMMIT, FALSE);

			if(count($queries) <= 0)
				return;

			try
			{
				if(!$this->getConnection()->beginTransaction())
				{
					print_r(">>>".$this->getConnection()->errorInfo());
					die();
				}

				foreach($queries as $query)
				{
					$results[] = $this->getConnection()->exec($query);
				}
			}
			catch(PDOException $e)
			{
				$this->getConnection()->rollBack();
			}

			return $results;
		}

		public static function disableFKQuery($query)
		{
			$disableFKChecks = <<<EOD
SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';
EOD;

			$enableFKChecks = <<<EOD
SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
EOD;

			return "$disableFKChecks\n\n$query\n\n$enableFKChecks";
		}

		public function tableExists($name)
		{
			$result = $this->execute("SHOW TABLES LIKE '$name'", PDO::FETCH_COLUMN, false);
			return count($result) <= 0 ? false : $result[0];
		}
	}