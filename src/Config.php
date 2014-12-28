<?php

class Config
{
    public $key, $secret, $region;

    function __construct($dir, $profileName)
    {
        $file = $dir . '/.aws/config';
        if (!$config = parse_ini_file($file, true)) {
            throw new Exception("Missing or wrong config in '$file'");
        }
        if (!$profile = $config[$profileName]) {
            throw new Exception("No profile found with name: '$profileName'");
        }

        $this->key = $profile['aws_access_key_id'];
        $this->secret = $profile['aws_secret_access_key'];
        $this->region = $profile['region'];
    }
}
