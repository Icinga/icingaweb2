<?php

/**
 * Icinga Livestatus Backend
 *
 * @package Monitoring
 */
namespace Icinga\Module\Monitoring\Backend;

use Icinga\Protocol\Livestatus\Connection;

/**
 * This class provides an easy-to-use interface to the Livestatus socket library
 *
 * You should usually not directly use this class but go through Icinga\Backend.
 *
 * @copyright  Copyright (c) 2013 Icinga-Web Team <info@icinga.org>
 * @author     Icinga-Web Team <info@icinga.org>
 * @package    Icinga\Application
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class Livestatus extends AbstractBackend
{
    protected $connection;

    /**
     * Backend initialization starts here
     *
     * return void
     */
    protected function init()
    {
        $this->connection = new Connection($this->config->socket);
    }

    /**
     * Get our Livestatus connection
     *
     * return \Icinga\Protocol\Livestatus\Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }
}
