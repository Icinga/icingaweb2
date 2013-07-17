<?php

namespace Test\Monitoring\Testlib\Datasource\Strategies;


class StatusdatSetupStrategy implements SetupStrategy {

    private function requireFolder($folder)
    {
        $files = scandir($folder);
        foreach($files as $file) {
            if ($file[0] == ".") {
                continue;
            }
            if (is_dir($folder."/".$file)) {
                $this->requireFolder($folder."/".$file);
            } elseif (preg_match("/\.php/", $file)) {
                require_once($folder."/".$file);
            }
        }
    }

    private function requireStatusDat()
    {
        $moduleDir = realpath(dirname(__FILE__)."/../../../../../");
        $appDir = realpath($moduleDir."/../../");
        $base = $appDir."/library/Icinga/Protocol/StatusDat";
        require_once("Zend/Cache.php");
        require_once("Zend/Log.php");
        require_once($appDir."/library/Icinga/Application/Logger.php");
        require_once($appDir."/library/Icinga/Protocol/AbstractQuery.php");
        require_once($base."/Exception/ParsingException.php");
        require_once($base."/Query/IQueryPart.php");
        require_once($base."/IReader.php");
        $this->requireFolder($base);

    }

    public function setup($version = null, $resource = null)
    {
        if ($resource == null) {
            $resource = array(
                "status_file" => "/tmp/teststatus.dat",
                "objects_file" => "/tmp/testobjects.cache"
            );
        }
        $this->requireStatusDat();
        $this->teardown($resource);
        touch($resource["status_file"]);
        touch($resource["objects_file"]);
        return $resource;
    }

    public function teardown($resource = null)
    {

        if (file_exists($resource["status_file"])) {
            unlink($resource["status_file"]);
        }
        if (file_exists($resource["objects_file"])) {
            unlink($resource["objects_file"]);
        }
    }
}