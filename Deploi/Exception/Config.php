<?php

    namespace Deploi\Exception;

    use ErrorException;

    class Config extends ErrorException
    {
        const INVALID_CONFIG_PATH = "Invalid configuration path supplied: '%s'";
    }
