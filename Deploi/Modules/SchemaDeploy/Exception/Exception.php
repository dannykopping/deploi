<?php
	namespace Deploi\Modules\SchemaDeploy\Exception;

	use ErrorException;

	/**
	 *
	 */
	class Exception extends ErrorException
	{
		const BINARY_NOT_FOUND = "Could not find the mysqldump binary at location '%s'";
		const CANNOT_CONNECT = "Cannot connect to MySQL server with DSN '%s' with user '%s'";
	}
