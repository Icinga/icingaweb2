<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web;

use Icinga\Application\Icinga;
use Icinga\Web\FileCache;
use Icinga\Web\LessCompiler;

class StyleSheet
{
    protected static $lessFiles = array(
        '../application/fonts/fontello-ifont/css/ifont-embedded.css',
        'css/vendor/tipsy.css',
        'css/icinga/defaults.less',
        'css/icinga/animation.less',
        'css/icinga/layout-colors.less',
        'css/icinga/layout-structure.less',
        'css/icinga/menu.less',
        'css/icinga/header-elements.less',
        'css/icinga/main-content.less',
        'css/icinga/tabs.less',
        'css/icinga/forms.less',
        'css/icinga/setup.less',
        'css/icinga/widgets.less',
        'css/icinga/pagination.less',
        'css/icinga/monitoring-colors.less',
        'css/icinga/selection-toolbar.less',
        'css/icinga/login.less',
        'css/icinga/controls.less'
    );

    public static function compileForPdf()
    {
        self::checkPhp();
        $less = new LessCompiler();
        $basedir = Icinga::app()->getBootstrapDirectory();
        foreach (self::$lessFiles as $file) {
            $less->addFile($basedir . '/' . $file);
        }
        $less->addLoadedModules();
        $less->addFile($basedir . '/css/pdf/pdfprint.less');
        return $less->compile();
    }

    public static function sendMinified()
    {
        self::send(true);
    }

    protected static function fixModuleLayoutCss($css)
    {
        return preg_replace(
            '/(\.icinga-module\.module-[^\s]+) (#layout\.[^\s]+)/m',
            '\2 \1',
            $css
        );
    }

    protected static function checkPhp()
    {
        // PHP had a rather conservative PCRE backtrack limit unless 5.3.7
        if (version_compare(PHP_VERSION, '5.3.7') <= 0) {
            ini_set('pcre.backtrack_limit', 1000000);
        }
    }

    public static function send($minified = false)
    {
        self::checkPhp();
        $app = Icinga::app();
        $basedir = $app->getBootstrapDirectory();
        foreach (self::$lessFiles as $file) {
            $lessFiles[] = $basedir . '/' . $file;
        }
        $files = $lessFiles;
        foreach ($app->getModuleManager()->getLoadedModules() as $name => $module) {
            if ($module->hasCss()) {
                $files[] = $module->getCssFilename();
            }
        }

        if ($etag = FileCache::etagMatchesFiles($files)) {
            header("HTTP/1.1 304 Not Modified");
            return;
        } else {
            $etag = FileCache::etagForFiles($files);
        }
        header('Cache-Control: public');
        header('ETag: "' . $etag . '"');
        header('Content-Type: text/css');

        $min = $minified ? '.min' : '';
        $cacheFile = 'icinga-' . $etag . $min . '.css';
        $cache = FileCache::instance();
        if ($cache->has($cacheFile)) {
            $cache->send($cacheFile);
            return;
        }

        $less = new LessCompiler();
        foreach ($lessFiles as $file) {
            $less->addFile($file);
        }
        $less->addLoadedModules();
        if ($minified) {
            $less->compress();
        }
        $out = self::fixModuleLayoutCss($less->compile());
        $cache->store($cacheFile, $out);
        echo $out;
    }
}
