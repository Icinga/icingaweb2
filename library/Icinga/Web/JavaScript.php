<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web;

use Icinga\Application\Icinga;
use JShrink\Minifier;

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
        'js/vendor/jquery-2.1.0',
        'js/vendor/jquery.sparkline',
        'js/vendor/jquery.tipsy'
    );

    protected static $ie8VendorFiles = array(
        'js/vendor/jquery-1.11.0',
        'js/vendor/jquery.sparkline',
        'js/vendor/jquery.tipsy'
    );

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
        return self::send(true);
    }

    public static function sendForIe8()
    {
        self::$vendorFiles = self::$ie8VendorFiles;
        return self::send();
    }

    public static function send($minified = false)
    {
        header('Content-Type: application/javascript');
        $basedir = Icinga::app()->getBootstrapDirecory();

        $js = $out = '';
        $min = $minified ? '.min' : '';

        // TODO: Cache header
        header('Content-Type: application/javascript');
        $cacheFile = '/tmp/cache_icinga' . $min . '.js';
        if (file_exists($cacheFile)) {
            readfile($cacheFile);
            exit;
        }

        // We do not minify vendor files
        foreach (self::$vendorFiles as $file) {
            $out .= file_get_contents($basedir . '/' . $file . $min . '.js');
        }

        foreach (self::$jsFiles as $file) {
            $js .= file_get_contents($basedir . '/' . $file);
        }

        foreach (Icinga::app()->getModuleManager()->getLoadedModules() as $name => $module) {
            if ($module->hasJs()) {
                $js .= file_get_contents($module->getJsFilename());
            }
        }
        if ($minified) {
            require_once 'IcingaVendor/JShrink/Minifier.php';
            $out .= Minifier::minify($js, array('flaggedComments' => false));
        } else {
            $out .= $js;
        }
        // Not yet, this is for tests only. Waiting for Icinga\Web\Cache
        // file_put_contents($cacheFile, $out);
        echo $out;
    }
}
