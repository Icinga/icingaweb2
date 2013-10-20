<?php

namespace Icinga\Web;

class Topbar
{
    private static $partials = array();

    public static function addPartial($viewScriptName, $moduleName, $data)
    {
        self::$partials[] = array(
            'viewScriptName'    => $viewScriptName,
            'moduleName'        => $moduleName,
            'data'              => $data
        );
    }

    public static function getPartials()
    {
        return self::$partials;
    }
}
