<?php
require 'util.php';

$gitUrl = json_decode($_POST['payload'], true)['repository']['url'];
$start = isStartParam() ? '&start' : '';
$stop = isStopParam()  ? '&stop' : '';
file_get_contents('http://localhost/master-publish.php?gitUrl=' . $gitUrl . $start . $stop);
