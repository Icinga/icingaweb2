<?php

$baseDir = $_SERVER['DOCUMENT_ROOT'];

if ($_SERVER['REQUEST_URI'] === '/css/icinga.css') {
    include $baseDir . '/css.php';
} elseif ($_SERVER['REQUEST_URI'] === '/js/icinga.min.js') {
    include $baseDir . '/js.php';
} elseif (file_exists($baseDir . $_SERVER['REQUEST_URI'])) {
    return false;
} else {
    include $baseDir . '/index.php';
}

