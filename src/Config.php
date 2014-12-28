<?php

class Config
{
    public $key, $secret, $region;

    function __construct($dir, $profile)
    {
        $file = $dir . '/.aws/config';
        if (!$config = parse_ini_file($file, true)) {
            throw new Exception("Missing or wrong config in $file");
        }
        if (!$profile = $config[$profile]) {
            throw new Exception("No profile found with name: $profile");
        }

        $this->$key = $profile['aws_access_key_id'];
        $this->$secret = $profile['aws_secret_access_key'];
        $this->$region = $profile['region'];
    }
}
