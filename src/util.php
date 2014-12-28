<?php
function run($desc, $action)
{
    $start = microtime(true);
    error_log($desc);
    try {
        $action();
        error_log('Success!');
    } catch (Exception $e) {
        error_log($e->getMessage());
    }
    error_log('Request processed in ' . number_format((microtime(true) - $start), 3) . 's');
}