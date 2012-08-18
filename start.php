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
    $set->setBasePath(__DIR__);
    $set->setPaths(array(__DIR__));
    $set->setExclusions(array("\/\.+", "\.conf", "archive(.+)?\.[zip|tar\.gz]"));

	$creds = new Credentials("192.168.2.100", "dev", "dev", 22, Credentials::SERVER);
	$creds->setWebroot("/var/www");

    // archive example
//    $z = new Archive($set);
//
//    $tmp = tempnam(sys_get_temp_dir(), "deploi/archive");
//    $z->save($tmp);
//
//	echo $tmp;

    $deploy = new Deploy($set, $creds);