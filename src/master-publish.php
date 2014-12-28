<?php

require 'Config.php';
require 'Profile.php';
require 'Ec2.php';
require 'util.php';

define('CONFIG_DIR', '/usr/home/nidiag'); //to adjust
define('WAIT_INTERVAL', 15);
define('MAX_WAITS', 10);

processRequest('Processing master publish request for ' . $_GET['gitUrl'], function () {
    $config = new Config(CONFIG_DIR, 'default');
    $profile = new Profile(CONFIG_DIR, $_GET['gitUrl']);
    $ec2 = new Ec2($config, $profile->awsInstanceName);

    if (isStartParam()) {
        error_log("Starting instance '{$profile->awsInstanceName}'");
        $ec2->waitToStart();
    }

    for ($i = 0; $i < MAX_WAITS; $i++) {
        error_log('Requesting publish to ' . $profile->gitUrl);
        if (@file_get_contents('http://' . $ec2->publicIp() . '/publish.php?gitUrl=' . $profile->gitUrl) !== false) {
            break;
        }
        sleep(WAIT_INTERVAL);
    }

    if (isStopParam()) {
        error_log("Stopping instance '{$profile->awsInstanceName}'");
        $ec2->waitToStop();
    }

    if ($i === MAX_WAITS) {
        throw new Exception("Could not publish");
    }
});