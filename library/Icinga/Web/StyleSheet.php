<?php

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
        'css/icinga/pagination.less',
        'css/icinga/monitoring-colors.less',
        'css/icinga/login.less',
    );

    public static function send()
    {
        header('Content-Type: text/css');
        $less = new LessCompiler();
        $basedir = Icinga::app()->getBootstrapDirecory();
        foreach (self::$lessFiles as $file) {
          $less->addFile($basedir . '/' . $file);
        }
        echo $less->addLoadedModules()->compile();
    }
}
