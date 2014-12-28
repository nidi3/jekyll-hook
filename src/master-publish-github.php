<?php
$gitUrl = json_decode($_POST['payload'], true)['repository']['url'];
file_get_contents("http://localhost/master-publish.php?gitUrl=" . $gitUrl . '&start=' . $_GET['start'] . '&stop=' . $_GET['stop']);
