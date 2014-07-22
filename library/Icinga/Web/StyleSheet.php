<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web;

use Icinga\Application\Icinga;
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
        header('Content-Type: text/css');

        $min = $minified ? '.min' : '';
        $cacheFile = '/tmp/cache_icinga' . $min . '.css';
        if (file_exists($cacheFile)) {
            readfile($cacheFile);
            exit;
        }

        $less = new LessCompiler();
        $basedir = Icinga::app()->getBootstrapDirecory();
        foreach (self::$lessFiles as $file) {
            $less->addFile($basedir . '/' . $file);
        }
        $less->addLoadedModules();
        if ($minified) {
            $less->compress();
        }
        $out = $less->compile();
        // Not yet, this is for tests only. Waiting for Icinga\Web\Cache
        // file_put_contents($cacheFile, $out);
        echo $out;

    }
}
