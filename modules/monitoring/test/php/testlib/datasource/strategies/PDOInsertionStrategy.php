<?php

namespace Test\Monitoring\Testlib\Datasource\Strategies;
use \Test\Monitoring\Testlib\DataSource\TestFixture;

class PDOInsertionStrategy {
    private $objectId = 0;
    private $fixture;
    private $connection;

    public $datetimeFormat = "U";

    public function setConnection($connection) {
        $this->connection = $connection;
    }

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


    private function insertHosts()
    {
        $hosts = &$this->fixture->getHosts();

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
            '(host_object_id, current_state, last_check, notifications_enabled, '.
            'active_checks_enabled, passive_checks_enabled, is_flapping, scheduled_downtime_depth)'.
            ' VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $insertCVQuery = $this->connection->prepare(
            'INSERT INTO icinga_customvariablestatus'.
            '(object_id, varname, varvalue) VALUES (?, ?, ?)'
        );
        foreach($hosts as &$host) {
            $flags = $host["flags"];

            $insertObjectQuery->execute(array($this->objectId, $host["name"]));
            $insertHostQuery->execute(array(
                $this->objectId, $host["name"], $host["name"], $host["address"], $this->objectId,
                $host["icon_image"], $host["notes_url"], $host["action_url"]
            ));
            $insertHostStatusQuery->execute(array(
                $this->objectId, $host["state"], date($this->datetimeFormat, $flags->time), $flags->notifications,
                $flags->active_checks, $flags->passive_checks, $flags->flapping, $flags->in_downtime));

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
            '(service_object_id, current_state, last_check, notifications_enabled, '.
            'active_checks_enabled, passive_checks_enabled, is_flapping, scheduled_downtime_depth)'.
            ' VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $insertContactQuery = $this->connection->prepare(
            'INSERT INTO icinga_service_contacts (host_id, contact_object_id) VALUES (?, ?);'
        );
        $insertCVQuery = $this->connection->prepare(
            'INSERT INTO icinga_customvariablestatus'.
            '(object_id, varname, varvalue) VALUES (?, ?, ?)'
        );

        foreach($services as &$service) {
            $flags = $service["flags"];

            $insertObjectQuery->execute(array($this->objectId, $service["host"]["name"], $service["name"]));
            $insertServiceQuery->execute(array(
                $this->objectId, $service['host']['object_id'], $this->objectId, $service['name'],
                $service["notes_url"], $service["action_url"], $service["icon_image"]
            ));
            $insertServiceStatusQuery->execute(array(
                $this->objectId, $service["state"], date($this->datetimeFormat, $flags->time), $flags->notifications,
                $flags->active_checks, $flags->passive_checks, $flags->flapping, $flags->in_downtime));

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

    private function insertContacts()
    {
        $insertObjectQuery = $this->connection->prepare(
            'INSERT INTO icinga_objects (object_id, objecttype_id, name1) VALUES (?, 10, ?);'
        );
        $insertContactQuery = $this->connection->prepare(
            'INSERT INTO icinga_contacts (contact_object_id, alias) VALUES (?, ?);'
        );
        $contacts = &$this->fixture->getContacts();
        foreach($contacts as &$contact) {
            $insertObjectQuery->execute($this->objectId, $contact["alias"]);
            $insertContactQuery->execute($this->objectId, $contact["alias"]);
            $contact["object_id"] = $this->objectId;
            $this->objectId++;
        }
    }

    private function insertComments()
    {
        $insertCommentsQuery = $this->connection->prepare(
            'INSERT INTO icinga_comments (object_id, comment_type, author_name, comment_data) VALUES (?, ?, ?, ?);'
        );
        $comments = &$this->fixture->getComments();
        foreach ($comments as $comment) {
            if (isset($comment["host"])) {
                $type = 1;
                $object_id = $comment["host"]["object_id"];
            } elseif (isset($comment["service"])) {
                $type = 2;
                $object_id = $comment["service"]["object_id"];
            }
            $insertCommentsQuery->execute(array($object_id, $type, $comment["author"], $comment["text"]));
        }
    }

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
        $hostgroups = &$this->fixture->getHostgroups();

        foreach ($hostgroups as &$hostgroup) {
            $insertObjectQuery->execute(array($this->objectId, $hostgroup["name"]));
            $insertHostgroupQuery->execute(array($this->objectId, $this->objectId, $hostgroup["name"]));
            foreach ($hostgroup["members"] as $member) {
                $insertHostgroupMemberQuery->execute(array($this->objectId, $member["object_id"]));
            }
            $this->objectId++;
        }

    }

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
        $servicegroups = &$this->fixture->getServicegroups();

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