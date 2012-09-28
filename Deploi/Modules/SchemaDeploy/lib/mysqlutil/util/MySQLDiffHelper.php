<?php
	namespace Deploi\Modules\SchemaDeploy\lib\MySQLUtil\Util;

	use Deploi\Modules\SchemaDeploy\lib\MySQLUtil\Tasks\SchemaDiff;
	use Deploi\Util\Config;
	use Deploi\Modules\SchemaDeploy\lib\MySQLUtil\Util\Server;
	use Exception;

    /**
     *
     */
    class MySQLDiffHelper
    {
        public static function getDiff(Server $s1, Server $s2, $tables)
        {
			$lib = __DIR__."/../lib/";
			$args = self::getDiffArgs($s1, $s2, $tables);
			$output = `PYTHONPATH=$lib python $lib/scripts/mysqldiff.py $args`;
            new MySQLDiffOutputParser($s1, $s2, $output);
        }

        private static function getDiffArgs(Server $local, Server $remote, $tables)
        {
			// mysqldiff syntax:
            //  --server1=root:pass@localhost --server2=root:pass@dev localdb.test2:remotedb.test2 localdb.test1:remotedb.test1 -d sql --force --show-reverse -q --changes-for=server2 --xml

            $options = array();

            // generate server options
            $options[] = self::getServerOption($local, 1);
            $options[] = self::getServerOption($remote, 2);

            // generate table comparisons
            if(!empty($tables))
                $options[] = self::getTableOptions($local, $remote, $tables);
            else
                $options[] = "{$local->schema}:{$remote->schema}";

			$direction = Config::get("schemadeploy.direction");

			/**
				@see Deploi/Modules/SchemaDeploy/lib/MySQLUtil/lib/mysqldiff.rst for more info

				 -d DIFFTYPE sql  (display differences in SQL format)
				 --force          (do not abort when diff test fails)
				 --show-reverse   (produce a transformation report containing SQL)
				 -q               (quiet)
				 --xml            (output XML only)
				 --changes-for=   (get the changes for a server)
			*/

			if(!in_array($direction, array("forward", "reverse")))
				throw new Exception("$direction is not an acceptable value for schemadeploy.direction");

			$changesFor  = "changes-for=".($direction == "forward" ? "server2" : "server1");
            $options[] = "-d sql --force --show-reverse -q --$changesFor --xml";

            return implode(" ", $options);
        }

        private static function getServerOption(Server $server, $index=1)
        {
            return "--server".$index."={$server->username}:{$server->password}@{$server->host}:{$server->port}";
        }

        private static function getTableOptions(Server $local, Server $remote, $tables)
        {
            if(empty($tables))
                return "";

            $options = array();
            foreach($tables as $table)
            {
                $table = trim($table);
                $options[] = "{$local->schema}.$table:{$remote->schema}.$table";
            }

            return implode(" ", $options);
        }
    }
