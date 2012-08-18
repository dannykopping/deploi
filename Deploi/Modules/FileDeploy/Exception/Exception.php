<?php
	namespace Deploi\Modules\FileDeploy\Exception;

	use ErrorException;

	/**
	 *
	 */
	class Exception extends ErrorException
	{
		const CONNECTION_FAILURE = "Could not connect to server (hostname: '%s') with given credentials";
		const INVALID_HOST = "Invalid host: '%s'";
		const INVALID_WEBROOT = "Invalid webroot: '%s'";
		const COMMAND_FAILURE = "Command '%s' failed to execute: '%s'";
		const PATH_CREATION_FAILURE = "Path '%s' cannot be created. Reason: '%s'";
	}
