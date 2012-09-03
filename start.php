<?php
	use Deploi\Util\Config;
	use Deploi\Modules\SCMDeploy\GitDeploy;
	use Deploi\Util\SCM\Git\Git;
	use Deploi\Util\Security\Credentials;
	use Deploi\Modules\FileDeploy\Deploy;
	use Deploi\Modules\FileDeploy\SFTP;
	use Deploi\Util\File\FileSet;
	use Deploi\Modules\Archive\Archive;

	include_once "load-lib.php";

	/**
	 *
	 */

	Config::setConfigPath("conf/");

	$set = new FileSet();
	$set->setBasePath("to-deploy/");
	$set->setPaths(array("to-deploy/"));
	$set->setExclusions(array("\/\.+", "\.bad", "\.git/*"));

	$creds = new Credentials();
	$creds->setHost(Config::get("filedeploy.host"));
	$creds->setUsername(Config::get("filedeploy.user"));
	$creds->setPassword(Config::get("filedeploy.pass"));
	$creds->setPort(Config::get("filedeploy.port"));
	$creds->setWebroot(Config::get("filedeploy.webroot"));

	$testRepo = sys_get_temp_dir().DIRECTORY_SEPARATOR."test-repo";
	$gg = new GitDeploy();
	$gg->getPayload();

	$set->setBasePath($testRepo);
	$set->setPaths(null);

	$payload = new Archive($set);


	$deploy = new Deploy(, $creds);