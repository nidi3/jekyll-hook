<?php
/**
 * Takes a github webhook request, transforms it and calls publish.php.
 */

require 'util.php';

file_get_contents('http://localhost/publish.php?gitUrl=' . findGithubUrl());