<?php

$start = microtime(true);
try {
    process();
} catch (Exception $e) {
    error_log($e->getMessage());
}
error_log('Request processed in ' . number_format((microtime(true) - $start), 3) . 's');


function process()
{
    $homedir = $_SERVER['DOCUMENT_ROOT'] . '/../';

    $profilesFile = $homedir . 'profiles.ini';
    if (!$profiles = parse_ini_file($profilesFile, true)) {
        throw new Exception('Missing or wrong profiles at: ' . $profilesFile);
    }
    $gitUrl = json_decode($_POST['payload'], true)['repository']['url'];
    if (!$profileName = findProfile($profiles, $gitUrl)) {
        throw new Exception('no profile for git url: ' . $gitUrl);
    }

    $profile = $profiles[$profileName];
    $gitUser = $profile['gitUser'];
    $gitPass = $profile['gitPassword'];
    $awsBucket = $profile['awsBucket'];

    if (!$awsBucket) {
        throw new Exception('no aws bucket defined for profile: ' . $profileName);
    }
    $awsConfig = $homedir . '.aws/config';
    if (!file_exists($awsConfig)) {
        throw new Exception('no aws config file found at: ' . $awsConfig);
    }
    if (!is_readable($awsConfig)) {
        throw new Exception('aws config file is not readable at: ' . $awsConfig);
    }

    $pathPos = strrpos($gitUrl, '/');
    $path = substr($gitUrl, $pathPos + 1);
    $dest = '/tmp/git/' . $path;

    if (file_exists($dest)) {
        execute('cd ' . $dest . '; git pull');
    } else {
        $pos = strpos($gitUrl, '://');
        $gitUrl = substr($gitUrl, 0, $pos + 3) . ($gitUser ? $gitUser . ':' . $gitPass . '@' : '') . substr($gitUrl, $pos + 3, strlen($gitUrl) - 3);
        execute('git clone ' . $gitUrl . ' ' . $dest);
    }
    execute('cd ' . $dest . '; jekyll build');
    execute('aws s3 sync ' . $dest . '/_site ' . $awsBucket . ' --delete --size-only');

    error_log('published!');
}

function findProfile($profiles, $gitUrl)
{
    foreach ($profiles as $key => $value) {
        if ($value['gitUrl'] === $gitUrl) return $key;
    }
}

function execute($cmd)
{
    error_log('executing "' . $cmd . '"');
    exec($cmd, $out, $code);
    if ($code !== 0) {
        throw new Exception('Error executing "' . $cmd . '": ' . join('\n', $out));
    }
}

?>