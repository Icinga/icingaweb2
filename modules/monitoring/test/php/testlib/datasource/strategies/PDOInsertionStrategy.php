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
use \Test\Monitoring\Testlib\DataSource\TestFixture;

/**
 * TestFixture insertion implementation for PDO based backends
 *
 * This class allows to create the actual IDO databases from TestFixture
 * classes using PDO.
 *
 */
class PDOInsertionStrategy
{
    /**
     * Points to the (icinga) objectId of the next inserted object
     * @var int
     */
    private $objectId = 0;

    /**
     * The fixture that is being inserted by this object
     * @var TestFixture
     */
    private $fixture;

    /**
     * The database (PDO) connection to use for inserting
     * @var \PDO
     */
    private $connection;

    /**
     * The date format that will be used for inserting
     * date values, see @link http://php.net/manual/en/function.date.php
     * for possible values
     *
     * @var string
     */
    public $datetimeFormat = "U";

    /**
     * @see InsertionStrategy::setConnection
     *
     * @param \PDO $connection  The PDO connection to use
     */
    public function setConnection($connection) {
        $this->connection = $connection;
    }

    /**
     * Insert the provided @see TestFixture into this database
     *
     * @param TestFixture $fixture  The fixture to insert into the database
     */
    public function insert(TestFixture $fixture)
    {
        $this->fixture = $fixture;

        $this->insertContacts();

        $this->insertHosts();
        $this->insertServices();
        $this->insertComments();

        $this->insertHostgroups();
        $this->insertServicegroups();
    }

    /**
     * Insert all hosts from the current fixture into the IDO Database
     *
     * This method updates the icinga_objects, icinga_hosts, icinga_hoststatus
     * and icinga_customvariablestatus tables with the host values provided
     * by the internal fixture (@see PDOInsertStrategy::insert)
     *
     */
    private function insertHosts()
    {
        $hosts = $this->fixture->getHosts();

        $insertObjectQuery = $this->connection->prepare(
            'INSERT INTO icinga_objects (object_id, objecttype_id, name1, is_active) VALUES (?, 1, ?, 1);'
        );
        $insertHostQuery = $this->connection->prepare(
            'INSERT INTO icinga_hosts ('.
            'host_id, alias, display_name, address, host_object_id, '.
            'icon_image, notes_url, action_url'.
            ') VALUES (?, ?, ?, ?, ?, ?, ?, ?);'
        );
        $insertContactQuery = $this->connection->prepare(
            'INSERT INTO icinga_host_contacts (host_id, contact_object_id) VALUES (?, ?);'
        );
        $insertHostStatusQuery = $this->connection->prepare(
            'INSERT INTO icinga_hoststatus'.
            '(host_object_id, current_state, last_check, last_state_change, notifications_enabled, '.
            'active_checks_enabled, passive_checks_enabled, is_flapping, scheduled_downtime_depth,'.
            'output, long_output, '.
            'problem_has_been_acknowledged, has_been_checked) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $insertCVQuery = $this->connection->prepare(
            'INSERT INTO icinga_customvariablestatus'.
            '(object_id, varname, varvalue) VALUES (?, ?, ?)'
        );
        foreach($hosts as &$host) {
            $flags = $host["flags"];

            $insertObjectQuery->execute(array($this->objectId, $host["name"]));
            $insertHostQuery->execute(array(
                $this->objectId, $host["name"]." alias", $host["name"], $host["address"], $this->objectId,
                $host["icon_image"], $host["notes_url"], $host["action_url"]
            ));
            $insertHostStatusQuery->execute(array(
                $this->objectId, $host["state"], date($this->datetimeFormat, $flags->time),
                date($this->datetimeFormat, $flags->time), $flags->notifications, $flags->active_checks,
                $flags->passive_checks, $flags->flapping, $flags->in_downtime, "Plugin output for host ".$host["name"],
                "Long plugin output for host ".$host["name"], $flags->acknowledged, $flags->is_pending == 0
            ));

            foreach($host["contacts"] as $contact) {
                $insertContactQuery->execute(array($this->objectId, $contact["object_id"]));
            }
            foreach($host["customvariables"] as $cvName=>$cvValue) {
                $insertCVQuery->execute(array($this->objectId, $cvName, $cvValue));
            }

            $host["object_id"] = $this->objectId;
            $this->objectId++;
        }
    }

    /**
     *  Insert all services from the provided fixture into the IDO database
     *
     *  This method updates the icinga_objects, icinga_services, icinga_servicestatus,
     *  icinga_service_contacts, icinga_customvariablestatus
     */
    private function insertServices()
    {
        $services = $this->fixture->getServices();
        $insertObjectQuery = $this->connection->prepare(
            'INSERT INTO icinga_objects (object_id, objecttype_id, name1, name2, is_active) VALUES (?, 2, ?, ?, 1);'
        );
        $insertServiceQuery = $this->connection->prepare(
            'INSERT INTO icinga_services ('.
            'service_id, host_object_id, service_object_id, display_name, '.
            'icon_image, notes_url, action_url'.
            ') VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $insertServiceStatusQuery = $this->connection->prepare(
            'INSERT INTO icinga_servicestatus'.
            '(service_object_id, current_state, last_check, last_state_change, notifications_enabled, '.
            'active_checks_enabled, passive_checks_enabled, is_flapping, scheduled_downtime_depth,'.
            'output, long_output, '.
            'problem_has_been_acknowledged, has_been_checked)  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) '
        );
        $insertContactQuery = $this->connection->prepare(
            'INSERT INTO icinga_service_contacts (host_id, contact_object_id) VALUES (?, ?);'
        );
        $insertCVQuery = $this->connection->prepare(
            'INSERT INTO icinga_customvariablestatus '.
            '(object_id, varname, varvalue) VALUES (?, ?, ?)'
        );

        foreach($services as &$service) {
            $flags = $service["flags"];

            $insertObjectQuery->execute(array($this->objectId, $service["host"]["name"], $service["name"]));
            $insertServiceQuery->execute(array(
                $this->objectId, $service['host']['object_id'], $this->objectId, $service['name'],
                $service["icon_image"], $service["notes_url"], $service["action_url"]
            ));
            $insertServiceStatusQuery->execute(array(
                $this->objectId, $service["state"], date($this->datetimeFormat, $flags->time),
                date($this->datetimeFormat, $flags->time), $flags->notifications, $flags->active_checks,
                $flags->passive_checks, $flags->flapping, $flags->in_downtime, "Plugin output for service ".$service["name"],
                "Long plugin output for service ".$service["name"], $flags->acknowledged,
                $flags->is_pending == 0 ? '1' : '0'
            ));

            foreach($service["contacts"] as $contact) {
                $insertContactQuery->execute(array($this->objectId, $contact["object_id"]));
            }

            foreach($service["customvariables"] as $cvName=>$cvValue) {
                $insertCVQuery->execute(array($this->objectId, $cvName, $cvValue));
            }

            $service["object_id"] = $this->objectId;
            $this->objectId++;
        }
    }

    /**
     *  Insert the contacts provided by the fixture into the database
     *
     *  This method updates the icinga_objects and icinga_contacts tables
     *  according to the provided fixture
     */
    private function insertContacts()
    {
        $insertObjectQuery = $this->connection->prepare(
            'INSERT INTO icinga_objects (object_id, objecttype_id, name1) VALUES (?, 10, ?);'
        );
        $insertContactQuery = $this->connection->prepare(
            'INSERT INTO icinga_contacts (contact_object_id, alias) VALUES (?, ?);'
        );
        $contacts = $this->fixture->getContacts();
        foreach($contacts as &$contact) {
            $insertObjectQuery->execute($this->objectId, $contact["alias"]);
            $insertContactQuery->execute($this->objectId, $contact["alias"]);
            $contact["object_id"] = $this->objectId;
            $this->objectId++;
        }
    }

    /**
     *  Insert comments provided by the fixture into the IDO database
     *
     *  This method updates the icinga_comments table according to the provided
     *  fixture
     */
    private function insertComments()
    {   $comment_id=0;
        $insertCommentsQuery = $this->connection->prepare(
            'INSERT INTO icinga_comments (object_id, comment_type, internal_comment_id, author_name, comment_data)'.
            ' VALUES (?, ?, ?, ?, ?);'
        );
        $comments = $this->fixture->getComments();
        foreach ($comments as $comment) {
            if (isset($comment["host"])) {
                $type = 1;
                $object_id = $comment["host"]["object_id"];
            } elseif (isset($comment["service"])) {
                $type = 2;
                $object_id = $comment["service"]["object_id"];
            }
            $insertCommentsQuery->execute(array(
                $object_id, $type, $comment_id++, $comment["author"], $comment["text"]
            ));
        }
    }

    /**
     *  Insert hostgroups from the provided fixture into the IDO database
     *
     *  This method updates the icinga_objects, icinga_hostgroups and icinga_hostgroup_members
     *  table with the values provide by the fixture
     */
    private function insertHostgroups()
    {
        $insertObjectQuery = $this->connection->prepare(
            'INSERT INTO icinga_objects (object_id, objecttype_id, name1) VALUES (?, 3, ?)'
        );
        $insertHostgroupQuery = $this->connection->prepare(
            'INSERT INTO icinga_hostgroups (hostgroup_id, hostgroup_object_id, alias) VALUES (?, ?, ?)'
        );
        $insertHostgroupMemberQuery = $this->connection->prepare(
            'INSERT INTO icinga_hostgroup_members (hostgroup_id, host_object_id) VALUES (?, ?)'
        );
        $hostgroups = $this->fixture->getHostgroups();

        foreach ($hostgroups as &$hostgroup) {
            $insertObjectQuery->execute(array($this->objectId, $hostgroup["name"]));
            $insertHostgroupQuery->execute(array($this->objectId, $this->objectId, $hostgroup["name"]));
            foreach ($hostgroup["members"] as $member) {
                $insertHostgroupMemberQuery->execute(array($this->objectId, $member["object_id"]));
            }
            $this->objectId++;
        }
    }

    /**
     *  Insert servicegroups from the provided fixture into the IDO database
     *
     *  This method updates the icinga_objects, icinga_servicegroups and icinga_servicegroup_members
     *  table with the values provide by the fixture
     */
    private function insertServicegroups()
    {
        $insertObjectQuery = $this->connection->prepare(
            'INSERT INTO icinga_objects (object_id, objecttype_id, name1) VALUES (?, 4, ?)'
        );
        $insertServicegroupQuery = $this->connection->prepare(
            'INSERT INTO icinga_servicegroups (servicegroup_id, servicegroup_object_id, alias) VALUES (?, ?, ?)'
        );
        $insertServicegroupMemberQuery = $this->connection->prepare(
            'INSERT INTO icinga_servicegroup_members (servicegroup_id, service_object_id) VALUES (?, ?)'
        );
        $servicegroups = $this->fixture->getServicegroups();

        foreach ($servicegroups as &$servicegroup) {
            $insertObjectQuery->execute(array($this->objectId, $servicegroup["name"]));
            $insertServicegroupQuery->execute(array($this->objectId, $this->objectId, $servicegroup["name"]));
            foreach ($servicegroup["members"] as $member) {
                $insertServicegroupMemberQuery->execute(array($this->objectId, $member["object_id"]));
            }
            $this->objectId++;
        }
    }
}
