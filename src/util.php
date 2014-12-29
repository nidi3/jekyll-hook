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

function isStartParam()
{
    return $_GET['start'] !== null;
}

function isStopParam()
{
    return $_GET['stop'] !== null;
}

function isBackgroundParam()
{
    return $_GET['background'] !== null;
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