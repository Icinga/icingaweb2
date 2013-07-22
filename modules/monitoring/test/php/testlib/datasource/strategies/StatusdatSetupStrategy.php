<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Test\Monitoring\Testlib\Datasource\Strategies;

use Tests\Icinga\Protocol\Statusdat\StatusdatTestLoader;
/**
 *  SetupStrategy for status dat.
 *
 *  This class is used for setting up a test enviromnent for querying
 *  statusdat fixtures.
 *
 */
class StatusdatSetupStrategy implements SetupStrategy {

    /**
     * Recursively require all php files underneath $folder
     *
     * @param String $folder    The folder to require
     */
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
                require_once(realpath($folder."/".$file));
            }
        }
    }

    /**
     *  Require all classes needed to work with the status.dat Reader
     *
     *  This includes the Status.dat Reader and Parser classes
     *  from Icinga/PRotocol as well as a few dependencies (Logging, Zend_Cache)
     *
     */
    private function requireStatusDat()
    {
        require_once 'library/Icinga/Protocol/Statusdat/StatusdatTestLoader.php';
        StatusdatTestLoader::requireLibrary();
    }

    /**
     * Create the status.dat and objects.cache files for using testfixtures
     *
     * Remove existing files for status.dat testfixtures and  create new
     * (empty) files at /tmp/ when no resource is given.
     *
     * @param String $version   The version to use, will be ignored
     * @param array $resource   An optional associative array pointing to the
     *                          objects_cache and status.dat files. The keys are as following:
     *                          - "status_file"     : Path to the status.dat to remove and recreate
     *                          - "objects_file"    : Path to the objects.cache file to remove and recreate
     * @return array            An path array (see $resource) that contains the used file paths
     */
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


    /**
     *  Remove test status.dat and objects.cache files
     *
     *  @param array $resource  An optional associative array pointing to the
     *                          objects_cache and status.dat files. The keys are as following:
     *                          - "status_file"     : Path to the status.dat to remove
     *                          - "objects_file"    : Path to the objects.cache file to remove
     */
    public function teardown($resource = null)
    {
        if ($resource == null) {
            $resource = array(
                "status_file" => "/tmp/teststatus.dat",
                "objects_file" => "/tmp/testobjects.cache"
            );
        }
        if (file_exists($resource["status_file"])) {
            unlink($resource["status_file"]);
        }
        if (file_exists($resource["objects_file"])) {
            unlink($resource["objects_file"]);
        }
    }
}