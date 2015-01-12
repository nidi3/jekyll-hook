<?php
/**
 * Get the contents of the git repo at $_GET['gitUrl'], run jekyll and publish it on an amazon s3.
 * Required files (see config/.aws):
 *   ~/.aws/config containing the aws key, secret and region as used by aws cli tools.
 *   ~/.aws/profiles containing profiles with git url, git credentials the aws s3 bucket to deploy the pages to.
 */

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
    $cache_configs = calc_caching($dest);
    foreach ($cache_configs as $cache_config) {
        execute("aws s3 sync $dest/_site {$profile->awsBucket} --delete --size-only {$cache_config}");
    }
});

function calc_caching($dir)
{
    $config = $dir . '/.caching';
    $res = array();
    if (file_exists($config) && $lines = parse_ini_file($config)) {
        foreach ($lines as $pattern => $expires) {
            $res[] = calc_cludes($lines, $pattern) . ' --expires=' . parse_expires($expires);
        }
    }
    return $res;
}

function calc_cludes($lines, $pattern)
{
    $cludes = '--include="' . $pattern . '"';
    foreach ($lines as $key => $value) {
        if ($key !== $pattern) {
            $cludes .= ' --exclude="' . $key . '"';
        }
    }
    return $cludes;
}

function parse_expires($e)
{
    $amount = 0;
    $factor = 0;
    if (preg_match('/\s*(\d+)\s*(\w+)\s*/', $e, $matches)) {
        $amount = $matches[1];
        if ($matches[2] === 'd') {
            $factor = 24 * 3600;
        }
    }
    if ($amount === 0 || $factor === 0) {
        $amount = $amount ? $amount : 1;
        $factor = $factor ? $factor : 24 * 3600;
        error_log("Unparseable expires date '$e', using $amount*$factor sec instead");
    }
    return gmdate('D, d M Y H:i:s T', time() + $amount * $factor);
}

function execute($cmd)
{
    error_log("executing '$cmd'");
    exec($cmd, $out, $code);
    if ($code !== 0) {
        throw new Exception("Error executing '$cmd': " . join('\n', $out));
    }
}
