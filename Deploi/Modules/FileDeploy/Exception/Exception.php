<?php
	namespace Deploi\Modules\FileDeploy\Exception;

	use ErrorException;

	/**
	 *
	 */
	class Exception extends ErrorException
	{
		const CONNECTION_FAILURE = "Could not connect to server (hostname: '%s') with given credentials";
		const CONNECTION_REFUSED = "Server '%s' refused connection";
		const INVALID_HOST = "Invalid host: '%s'";
		const INVALID_WEBROOT = "Invalid webroot: '%s'";
		const COMMAND_FAILURE = "Command '%s' failed to execute: '%s'";
		const PATH_CREATION_FAILURE = "Path '%s' cannot be created. Reason: '%s'";
		const FILE_CREATION_FAILURE = "File '%s' cannot be created. Reason: '%s'";
		const PERMISSION_FAILURE = "Path '%s' is not %s";
	}
