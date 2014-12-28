<?php

require 'Profile.php';
require 'Config.php';
require 'Ec2.php';
require 'util.php';

define('CONFIG_DIR', '/usr/home/nidiag'); //to adjust

run('Processing master publish request for ' . $_GET['gitUrl'], function () {
    $config = new Config(CONFIG_DIR, 'default');
    $profile = new Profile(CONFIG_DIR, $_GET['gitUrl']);
    $ec2 = new Ec2($config, $profile->awsInstanceName);

    if ($_GET['start']) {
        error_log("Starting {$profile->awsInstanceName}");
        $ec2->waitToStart();
    }

    error_log("Requesting publish to {$profile->gitUrl}");
    file_get_contents("http://{$ec2->publicIp()}/publish.php?gitUrl={$profile->$gitUrl}");

    if ($_GET['stop']) {
        error_log("Stopping {$profile->awsInstanceName}");
        $ec2->waitToStop();
    }
});


