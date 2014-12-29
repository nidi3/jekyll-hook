<?php
/**
 * Takes a github webhook request, transforms it and calls master-publish.php.
 */

require 'util.php';
require 'master-config.php';

file_get_contents('http://' . SELF_HOST . '/master-publish.php?' . masterPublishQuery(findGithubUrl(), true));
