<?php
    require_once("Deploi/Util/Versionable/Autoload/Autoload.php");

    $classLoader = new \Versionable\Autoload\Autoload();
    $classLoader->registerNamespace('Deploi', __DIR__);
    $classLoader->register();
?>