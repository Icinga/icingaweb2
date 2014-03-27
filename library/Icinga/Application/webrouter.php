<?php

namespace Icinga\Application;

use Icinga\Application\EmbeddedWeb;
use Icinga\Application\Web;
use Icinga\Web\StyleSheet;
use Icinga\Web\JavaScript;
use Icinga\Chart\Inline\PieChart;

error_reporting(E_ALL | E_STRICT);

if (array_key_exists('ICINGAWEB_CONFIGDIR', $_ENV)) {
    $configDir = $_ENV['ICINGAWEB_CONFIGDIR'];
} else {
    $configDir = '/etc/icingaweb';
}

$baseDir = $_SERVER['DOCUMENT_ROOT'];
$ruri = $_SERVER['REQUEST_URI'];
$remove = dirname($_SERVER['PHP_SELF']);

if (substr($ruri, 0, strlen($remove)) !== $remove) {
  return false;
}

$ruri = substr($ruri, strlen($remove));
list($path, $params) = preg_split('/\?/', $ruri, 2);
$ruriParts = preg_split('~/~', ltrim($ruri, '/'));
if (count($ruriParts) === 2 &&
    ($ruriParts[0] === 'css' || $ruriParts[0] === 'js')
) {

    require_once __DIR__ . '/EmbeddedWeb.php';
    EmbeddedWeb::start($configDir);

    switch($ruriParts[1]) {

        case 'icinga.css':
            Stylesheet::send();
            exit;
        case 'icinga.min.css':
            Stylesheet::sendMinified();
            exit;

        case 'icinga.dev.js':
            JavaScript::send();
            exit;

        case 'icinga.min.js':
            JavaScript::sendMinified();
            break;

        default:
            return false;
    }

} elseif ($path === '/svg/chart.php') {
    if (!array_key_exists('data', $_GET)) {
        return false;
    }
    require_once __DIR__ . '/EmbeddedWeb.php';
    EmbeddedWeb::start($configDir);
    header('Content-Type: image/svg+xml');
    $pie = new PieChart();
    $pie->initFromRequest();
    echo $pie->render();

} elseif (file_exists($baseDir . $ruri) && is_file($baseDir . $ruri)) {
    return false;
} else {
    require_once __DIR__ . '/Web.php';
    Web::start($configDir)->dispatch();
}

