<?php
$gitUrl = json_decode($_POST['payload'], true)['repository']['url'];
$start = $_GET['start'] !== null ? '&start' : '';
$stop = $_GET['stop'] !== null ? '&stop' : '';
file_get_contents('http://localhost/master-publish.php?gitUrl=' . $gitUrl . $start . $stop);
