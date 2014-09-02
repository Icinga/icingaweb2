<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web;

use Icinga\Application\Icinga;
use Icinga\Web\FileCache;
use Icinga\Web\LessCompiler;

class StyleSheet
{
    protected static $lessFiles = array(
        'css/icinga/defaults.less',
        'css/icinga/layout-colors.less',
        'css/icinga/layout-structure.less',
        'css/icinga/menu.less',
        'css/icinga/header-elements.less',
        'css/icinga/main-content.less',
        'css/icinga/tabs.less',
        'css/icinga/forms.less',
        'css/icinga/widgets.less',
        'css/icinga/pagination.less',
        'css/icinga/monitoring-colors.less',
        'css/icinga/selection-toolbar.less',
        'css/icinga/login.less',
    );

    public static function compileForPdf()
    {
        $less = new LessCompiler();
        $basedir = Icinga::app()->getBootstrapDirecory();
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

    public static function send($minified = false)
    {
        $app = Icinga::app();
        $basedir = $app->getBootstrapDirecory();
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
        $out = $less->compile();
        $cache->store($cacheFile, $out);
        echo $out;
    }
}
