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

	$creds = new Credentials("192.168.2.100", "dev", "dev", 22, Credentials::SERVER);
	$creds->setWebroot("/var/www");

//     archive example
//    $z = new Archive($set);
//
//    $tmp = tempnam(sys_get_temp_dir(), "archive");
//    $z->save($tmp);
//
//	echo $tmp;

    $deploy = new Deploy($set, $creds);