<?php
    use Deploi\Util\Config;
    use Deploi\Modules\Archive\Archive;

    include_once "load-lib.php";

    /**
     *
     */

    Config::setConfigPath("conf/");

    // archive example
    $z = new Archive(__DIR__, array(__DIR__), array("\/\.+", "\.conf", "archive(.+)?\.zip"));
    $z->save(__DIR__);
