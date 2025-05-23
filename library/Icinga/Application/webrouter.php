<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Application;

use Icinga\Chart\Inline\PieChart;
use Icinga\Web\Controller\StaticController;
use Icinga\Web\JavaScript;
use Icinga\Web\StyleSheet;

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

if (isset($_SERVER['REQUEST_URI'])) {
    $ruri = $_SERVER['REQUEST_URI'];
} else {
    return false;
}

// Workaround, PHPs internal Webserver seems to mess up SCRIPT_FILENAME
// as it prefixes it's absolute path with DOCUMENT_ROOT
if (preg_match('/^PHP .* Development Server/', $_SERVER['SERVER_SOFTWARE'])) {
    $script = basename($_SERVER['SCRIPT_FILENAME']);
    $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'] = '/' . $script;
    $_SERVER['SCRIPT_FILENAME'] = $_SERVER['DOCUMENT_ROOT']
      . DIRECTORY_SEPARATOR
      . $script;
}

$baseDir = $_SERVER['DOCUMENT_ROOT'];
$baseDir = dirname($_SERVER['SCRIPT_FILENAME']);

// Fix aliases
$remove = str_replace('\\', '/', dirname($_SERVER['PHP_SELF']));
if (substr($ruri, 0, strlen($remove)) !== $remove) {
    return false;
}
$ruri = ltrim(substr($ruri, strlen($remove)), '/');

if (strpos($ruri, '?') === false) {
    $params = '';
    $path = $ruri;
} else {
    list($path, $params) = preg_split('/\?/', $ruri, 2);
}

$special = array(
    'css/icinga.css',
    'css/icinga.min.css',
    'js/icinga.dev.js',
    'js/icinga.min.js'
);

if (in_array($path, $special)) {
    include_once __DIR__ . '/EmbeddedWeb.php';
    EmbeddedWeb::start();

    switch ($path) {
        case 'css/icinga.css':
            Stylesheet::send();
            exit;
        case 'css/icinga.min.css':
            Stylesheet::send(true);
            exit;

        case 'js/icinga.dev.js':
            JavaScript::send();
            exit;

        case 'js/icinga.min.js':
            JavaScript::sendMinified();
            break;

        default:
            return false;
    }
} elseif ($path === 'svg/chart.php') {
    if (!array_key_exists('data', $_GET)) {
        return false;
    }
    include __DIR__ . '/EmbeddedWeb.php';
    EmbeddedWeb::start();
    header('Content-Type: image/svg+xml');
    $pie = new PieChart();
    $pie->initFromRequest();
    $pie->toSvg();
} elseif ($path === 'png/chart.php') {
    if (!array_key_exists('data', $_GET)) {
        return false;
    }
    include __DIR__ . '/EmbeddedWeb.php';
    EmbeddedWeb::start();
    header('Content-Type: image/png');
    $pie = new PieChart();
    $pie->initFromRequest();
    $pie->toPng();
} elseif (substr($path, 0, 4) === 'lib/') {
    include_once __DIR__ . '/StaticWeb.php';
    $app = StaticWeb::start();
    (new StaticController())->handle($app->getRequest());
    $app->getResponse()->sendResponse();
} elseif (file_exists($baseDir . '/' . $path) && is_file($baseDir . '/' . $path)) {
    return false;
} else {
    include __DIR__ . '/Web.php';
    Web::start()->dispatch();
}
