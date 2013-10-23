<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
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
                "object_file" => "/tmp/testobjects.cache"
            );
        }
        $this->requireStatusDat();
        $this->teardown($resource);
        touch($resource["status_file"]);
        touch($resource["object_file"]);
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
                "object_file" => "/tmp/testobjects.cache"
            );
        }
        if (file_exists($resource["status_file"])) {
            unlink($resource["status_file"]);
        }
        if (file_exists($resource["object_file"])) {
            unlink($resource["object_file"]);
        }
    }
}
