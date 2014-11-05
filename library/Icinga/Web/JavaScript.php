<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web;

use Icinga\Application\Icinga;
use Icinga\Web\FileCache;
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
        'js/icinga/eventlistener.js',
        'js/icinga/events.js',
        'js/icinga/history.js',
        'js/icinga/module.js',
        'js/icinga/timezone.js',
        'js/icinga/behavior/tooltip.js',
        'js/icinga/behavior/sparkline.js',
        'js/icinga/behavior/tristate.js',
        'js/icinga/behavior/navigation.js',
        'js/icinga/behavior/form.js'
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

        // Prepare vendor file list
        $vendorFiles = array();
        foreach (self::$vendorFiles as $file) {
            $vendorFiles[] = $basedir . '/' . $file . $min . '.js';
        }

        // Prepare Icinga JS file list
        $jsFiles = array();
        foreach (self::$jsFiles as $file) {
            $jsFiles[] = $basedir . '/' . $file;
        }

        foreach (Icinga::app()->getModuleManager()->getLoadedModules() as $name => $module) {
            if ($module->hasJs()) {
                $jsFiles[] = $module->getJsFilename();
            }
        }
        $files = array_merge($vendorFiles, $jsFiles);

        if ($etag = FileCache::etagMatchesFiles($files)) {
            header("HTTP/1.1 304 Not Modified");
            return;
        } else {
            $etag = FileCache::etagForFiles($files);
        }
        header('Cache-Control: public');
        header('ETag: "' . $etag . '"');
        header('Content-Type: application/javascript');

        $cacheFile = 'icinga-' . $etag . $min . '.js';
        $cache = FileCache::instance();
        if ($cache->has($cacheFile)) {
            $cache->send($cacheFile);
            return;
        }

        // We do not minify vendor files
        foreach ($vendorFiles as $file) {
            $out .= file_get_contents($file);
        }

        foreach ($jsFiles as $file) {
            $js .= file_get_contents($file);
        }

        if ($minified) {
            require_once 'IcingaVendor/JShrink/Minifier.php';
            $out .= Minifier::minify($js, array('flaggedComments' => false));
        } else {
            $out .= $js;
        }
        $cache->store($cacheFile, $out);
        echo $out;
    }
}
