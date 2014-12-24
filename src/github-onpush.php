<?php
$gitUrl = json_decode($_POST['payload'], true)['repository']['url'];
file_get_contents("http://localhost/publish.php?gitUrl=" . $gitUrl);
?>