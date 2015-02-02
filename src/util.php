<?php
function processRequest($desc, $action)
{
    $start = microtime(true);
    error_log($desc);
    try {
        $action();
        error_log('Success!');
    } catch (Exception $e) {
        error_log('Error: ' . $e->getMessage());
    }
    error_log('Request processed in ' . number_format((microtime(true) - $start), 3) . 's');
}

function isParam($name){
    return isset($_GET[$name]) && $_GET[$name] !== null;
}

function isStartParam()
{
    return isParam('start');
}

function isStopParam()
{
    return isParam('stop');
}

function isRestoreParam()
{
    return isParam('restore');
}

function isBackgroundParam()
{
    return isParam('background');
}

function masterPublishQuery($gitUrl, $withBackground)
{
    $start = isStartParam() ? '&start' : '';
    $stop = isStopParam() ? '&stop' : '';
    $background = (isBackgroundParam() && $withBackground) ? '&background' : '';
    return 'gitUrl=' . $gitUrl . $start . $stop . $background;
}

function invokeHttp($host, $file)
{
    $msg = "GET $file HTTP/1.0\n";
    $msg .= "Host: $host\n";
    $msg .= "Content-Length: 0\n\n";
    $socket = fsockopen($host, 80);
    fwrite($socket, $msg);
    fflush($socket);
    fclose($socket);
}

function findGithubUrl()
{
    return json_decode($_POST['payload'], true)['repository']['url'];
}