<?php

namespace Icinga\Web;

use Icinga\Application\Icinga;
use JShrink\Minifier;

// @codingStandardsIgnoreStart
require_once ICINGA_LIBDIR . '/vendor/JShrink/Minifier.php';
// @codingStandardsIgnoreStop

class JavaScript
{
    protected static $jsFiles = array(
        'js/helpers.js',
        'js/icinga.js',
        'js/icinga/logger.js',
        'js/icinga/utils.js',
        'js/icinga/ui.js',
        'js/icinga/timer.js',
        'js/icinga/loader.js',
        'js/icinga/events.js',
        'js/icinga/history.js',
        'js/icinga/module.js',
    );

    protected static $vendorFiles = array(
        'js/vendor/jquery-2.1.0.min.js',
        'js/vendor/jquery.sparkline.min.js'
    );

    public static function listFiles()
    {
        return array_merge(self::$vendorFiles, self::$jsFiles, self::listModuleFiles());
    }

    public static function listModuleFiles()
    {
        $list = array();
        foreach (Icinga::app()->getModuleManager()->getLoadedModules() as $name => $module) {
            if ($module->hasJs()) {
                $list[] = 'js/' . $name . '/module.js';
            }
        }
        return $list;
    }

    public static function sendMinified()
    {
        header('Content-Type: application/javascript');
        $basedir = Icinga::app()->getBootstrapDirecory();

        $js = $out = '';

        // TODO: Cache header
        header('Content-Type: text/css');

        // We do not minify vendor files
        foreach (self::$vendorFiles as $file) {
            $out .= file_get_contents($basedir . '/' . $file);
        }

        foreach (self::$jsFiles as $file) {
          $js .= file_get_contents($basedir . '/' . $file);
        }

        foreach (Icinga::app()->getModuleManager()->getLoadedModules() as $name => $module) {
            if ($module->hasJs()) {
                $js .= file_get_contents($module->getJsFilename());
            }
        }

        $out .= Minifier::minify($js, array('flaggedComments' => false));
        echo $out;
    }
}
