<?php
/**
 * Forwards requests to publish.php running on an aws ec2 instance.
 * GET parameters:
 *   gitUrl: the git repo containing the sites
 *   start: start the aws ec2 instance if needed
 *   stop: stop the aws ec2 instance after publish.php is called
 * Required files (see config/.aws):
 *   CONFIG_DIR/.aws/config containing the aws key, secret and region as used by aws cli tools.
 *   CONFIG_DIR/.aws/profiles containing profiles with git url and name of the aws ec2 instance.
 * Required libs:
 *   The php aws sdk (https://github.com/aws/aws-sdk-php/releases) must be copied to ../aws (see Ec2.php).
 */

require 'Config.php';
require 'Profile.php';
require 'Ec2.php';
require 'util.php';

define('CONFIG_DIR', '/usr/home/nidiag'); //to adjust
define('WAIT_INTERVAL', 15);
define('MAX_WAITS', 10);

if (isBackgroundParam()) {
    runAfterResponse('work');
} else {
    work();
}

function work()
{
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
}