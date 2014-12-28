<?php
require 'Profile.php';
require 'Config.php';
require 'util.php';

processRequest('Processing publish request for "' . $_GET['gitUrl'], function () {
    $homedir = $_SERVER['DOCUMENT_ROOT'] . '/../';
    $profile = new Profile($homedir, $_GET['gitUrl']);
    new Config($homedir, 'default');

    $dest = '/tmp/git/' . $profile->repoName();
    if (file_exists($dest)) {
        execute("cd $dest; git pull");
    } else {
        execute("git clone {$profile->cloneUrl()} $dest");
    }

    execute("cd $dest; jekyll build");
    execute("aws s3 sync $dest/_site {$profile->awsBucket} --delete --size-only");
});


function execute($cmd)
{
    error_log("executing '$cmd'");
    exec($cmd, $out, $code);
    if ($code !== 0) {
        throw new Exception("Error executing '$cmd': " . join('\n', $out));
    }
}
