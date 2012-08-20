<?php
    use Deploi\Util\Config;
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
    $set->setExclusions(array("\/\.+", "\.bad"));

	$creds = new Credentials();
	$creds->setHost(Config::get("filedeploy.host"));
	$creds->setUsername(Config::get("filedeploy.user"));
	$creds->setPassword(Config::get("filedeploy.pass"));
	$creds->setPort(Config::get("filedeploy.port"));
	$creds->setWebroot(Config::get("filedeploy.webroot"));

//     archive example
//    $z = new Archive($set);
//
//    $tmp = tempnam(sys_get_temp_dir(), "archive");
//    $z->save($tmp);
//
//	echo $tmp;

    $deploy = new Deploy($set, $creds);