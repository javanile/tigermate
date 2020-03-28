<?php

namespace Javanile\Tigermate;

if (file_exists(__DIR__.'/vendor/autoload.php')) {
    include_once __DIR__.'/vendor/autoload.php';
}

spl_autoload_register(
    function ($class) {
        if (substr($class, 0, strlen(__NAMESPACE__)) === __NAMESPACE__) {
            include_once __DIR__ . '/src/' . strtr(substr($class, strlen(__NAMESPACE__)), '\\_', '//') . '.php';
        }
    }
);
