<?php

// Extends include path with lib directory
$libPath = realpath(__DIR__ . '/../lib');
set_include_path(get_include_path() . PATH_SEPARATOR . $libPath);

//
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

chdir(__DIR__ . '/../lib');
require_once __DIR__ . '/../lib/webservice.php';
