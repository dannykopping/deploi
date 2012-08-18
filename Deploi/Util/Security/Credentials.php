<?php
	namespace Deploi\Util\Security;

	/**
	 *    This class holds a set of server credentials
	 */
	class Credentials
	{
		private $host;
		private $username;
		private $password;
		private $port;
		private $type;
		private $webroot;

		const SERVER = "server";
		const DATABASE = "database";

		public function __construct($host = null, $username = null, $password = null, $port = null, $type = null)
		{
			if(!empty($host)) 			$this->setHost($host);
			if(!empty($username)) 		$this->setUsername($username);
			if(!empty($password)) 		$this->setPassword($password);
			if(!empty($port))	 		$this->setPort($port);
			if(!empty($type)) 			$this->setType($type);
		}

		public function setHost($host)
		{
			$this->host = $host;
		}

		public function getHost()
		{
			return $this->host;
		}

		public function setPassword($password)
		{
			$this->password = $password;
		}

		public function getPassword()
		{
			return $this->password;
		}

		public function setType($type)
		{
			$this->type = $type;
		}

		public function getType()
		{
			return $this->type;
		}

		public function setUsername($username)
		{
			$this->username = $username;
		}

		public function getUsername()
		{
			return $this->username;
		}

		public function setPort($port)
		{
			$this->port = $port;
		}

		public function getPort()
		{
			return $this->port;
		}

		public function setWebroot($webroot)
		{
			$this->webroot = $webroot;
			if(substr($this->webroot, strlen($this->webroot) - 1) == "/")
				$this->webroot = substr($this->webroot, 0, strlen($this->webroot) - 1);
		}

		public function getWebroot()
		{
			return $this->webroot;
		}
	}
