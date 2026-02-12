<?php

// Cache assets
$assetFile = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (preg_match('/\.(js|css|jpg|jpeg|png|eot|woff|woff2|ttf|svg|gif|html)$/i', $assetFile) && file_exists('../lib' . $assetFile)) {
    @mkdir(__DIR__ . dirname($assetFile), 0777, true);
    copy('../lib' . $assetFile, __DIR__ . $assetFile);
    header('Content-Type: '.(preg_match('/\.css$/i', $assetFile) ? 'text/css' : mime_content_type(__DIR__ . $assetFile)));
    header('Content-Length: '.filesize(__DIR__ . $assetFile));
    readfile(__DIR__ . $assetFile);
    exit();
}

use Tracy\Debugger;

require_once __DIR__ . '/../vendor/autoload.php';
//Debugger::enable(Debugger::Development);

// Extends include path with lib directory
$libPath = realpath(__DIR__ . '/../lib');
set_include_path(get_include_path() . PATH_SEPARATOR . $libPath);

//
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

chdir(__DIR__ . '/../lib');
require_once __DIR__ . '/../lib/index.php';
