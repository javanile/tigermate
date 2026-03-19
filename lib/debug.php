<?php

$debugError = false;
if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {
    if ($_SERVER['HTTP_REFERER']) {
        $query = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_QUERY);
        parse_str($query, $params);
        $debugError = strtolower(@$params['DEBUG_ERROR']) == 'yes';
    } else {
        $debugError = strtolower(@$_GET['DEBUG_ERROR']) == 'yes';
    }
} else {
    $debugError = strtolower(@$_GET['DEBUG_ERROR']) == 'yes';
}

define('DEBUG_ERROR', $debugError);
