<?php

namespace Tests\Icinga\Protocol\Statusdat;

use Test\Icinga\LibraryLoader;

require_once(realpath(dirname(__FILE__) . '/../../LibraryLoader.php'));

class StatusdatTestLoader extends LibraryLoader
{
    public static function requireLibrary()
    {
        $libPath = LibraryLoader::getLibraryPath();
        require_once 'Zend/Config.php';
        require_once 'Zend/Cache.php';
        require_once 'Zend/Log.php';
        require_once($libPath . '/Data/BaseQuery.php');
        require_once($libPath . '/Application/Logger.php');
        require_once($libPath . '/Filter/Filterable.php');
        require_once($libPath . '/Data/DatasourceInterface.php');
        $statusdat = realpath($libPath . '/Protocol/Statusdat/');
        require_once($statusdat . '/View/AccessorStrategy.php');
        require_once($statusdat . '/View/MonitoringObjectList.php');
        require_once($statusdat . '/ObjectContainer.php');
        require_once($statusdat . '/IReader.php');
        require_once($statusdat . '/RuntimeStateContainer.php');
        require_once($statusdat . '/Query.php');
        require_once($statusdat . '/Parser.php');
        require_once($statusdat . '/Reader.php');
        require_once($statusdat . '/TreeToStatusdatQueryParser.php');
        require_once($statusdat . '/Exception/ParsingException.php');
        require_once($statusdat . '/Query/IQueryPart.php');
        require_once($statusdat . '/Query/Expression.php');
        require_once($statusdat . '/Query/Group.php');
    }
}
