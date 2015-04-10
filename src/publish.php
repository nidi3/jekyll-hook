<?php
/**
 * Get the contents of the git repo at $_GET['gitUrl'], run jekyll and publish it on an amazon s3.
 *
 * Required files (see config/.aws):
 *   ~/.aws/config containing the aws key, secret and region as used by aws cli tools.
 *   ~/.aws/profiles containing profiles with git url, git credentials the aws s3 bucket to deploy the pages to.
 *
 * If the repo contains a file named '.s3' it is used to configure some meta data.
 * An example .s3 file is:
 *   assets/* = expires:100d,delete
 *   * = expires:1d,size-only
 * This defines the following:
 *   Files in the asset folder expire in 100 days and files existing in s3 but not in the repo are deleted
 *   All other files expire in 1 day and only the file size is checked to see if it must be copied to s3.
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
        execute("cd $dest; git fetch origin master");
        execute("cd $dest; git reset --hard FETCH_HEAD");
        execute("cd $dest; git clean -df");
    } else {
        execute("git clone {$profile->cloneUrl()} $dest");
    }

    execute("cd $dest; jekyll build");
    $s3_configs = calc_s3_configs($dest);
    foreach ($s3_configs as $s3_config) {
        execute("aws s3 sync $dest/_site {$profile->awsBucket} {$s3_config}");
    }
});

function calc_s3_configs($dir)
{
    $res = array();
    $config = $dir . '/.s3';
    if (file_exists($config) && $lines = parse_ini_file($config)) {
        foreach ($lines as $pattern => $flags) {
            $res[] = calc_cludes($lines, $pattern) . ' ' . parse_flags($flags);
        }
    } else {
        $res[] = '--delete --size-only';
    }
    return $res;
}

function calc_cludes($lines, $pattern)
{
    if ($pattern === '*' || $pattern === '/*') {
        $excludes = '';
        foreach ($lines as $key => $value) {
            if ($key !== $pattern) {
                $excludes .= '--exclude="' . $key . '" ';
            }
        }
        return $excludes;
    }
    return '--exclude=* --include="' . $pattern . '"';
}

function parse_flags($flags)
{
    $s = '';
    $fl = explode(',', $flags);
    foreach ($fl as $flag) {
        $kv = explode(':', $flag, 2);
        if (count($kv) === 1) {
            $kv[] = '';
        }
        list($key, $value) = $kv;
        switch ($key) {
            case 'expires':
                $value = parse_expires($value);
                break;
            case 'size-only':
            case 'content-type':
            case 'cache-control':
            case 'content-disposition':
            case 'content-encoding':
            case 'content-language':
            case 'exact-timestamps':
            case 'delete':
                break;
            default:
                error_log("Ignoring unknown option '$key'");
                $key = '';
        }
        if ($key) {
            $s .= '--' . $key . ($value ? ('="' . $value . '"') : '') . ' ';
        }
    }
    echo $s;
    return $s;
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
