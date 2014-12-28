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