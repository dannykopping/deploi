<?php
    namespace Deploi\Modules\FileDeploy;

    include_once __DIR__.DIRECTORY_SEPARATOR."lib/phpseclib/Net/SSH2.php";
    include_once __DIR__.DIRECTORY_SEPARATOR."lib/phpseclib/Net/SFTP.php";

    use Net_SSH2;

    /**
     *
     */

    class SFTP
    {
        public function __construct()
        {
            $ssh = new Net_SSH2("192.168.2.100");
            if(!$ssh->login("dev", "dev"))
            {
                die("Error logging in");
            }

            print_r($ssh->exec("cd / && ls -laR", true));
        }
    }
