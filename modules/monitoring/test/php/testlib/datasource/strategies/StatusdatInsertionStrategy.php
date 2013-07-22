<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Test\Monitoring\Testlib\Datasource\Strategies;
use Test\Monitoring\Testlib\DataSource\schemes\ObjectsCacheTemplates;
use \Test\Monitoring\Testlib\DataSource\TestFixture;
use \Test\Monitoring\Testlib\DataSource\schemes\StatusdatTemplates;

require_once(dirname(__FILE__).'/../schemes/ObjectsCacheTemplates.php');
require_once(dirname(__FILE__).'/../schemes/StatusdatTemplates.php');

/**
 *  An @see InsertionStrategy for creating status.dat and objects.cache
 *  files from a TestFixture
 *
 *  This class helps testing status.dat backends by writing testfixtures
 *  to according objects.cache and status.dat files which then can be read
 *  by the Statusdat parser and used in tests.
 *
 *  The templates for insertion can be found under schemes/objectsCacheTemplates.php
 *  and schemes/StatusdatTempaltes.php
 *
 */
class StatusdatInsertionStrategy implements InsertionStrategy {

    /**
     * The status.dat filename to write the object-state to
     * @var String
     */
    private $statusDatFile;

    /**
     * The objects.cache filename to write the object structure to
     * @var String
     */
    private $objectsCacheFile;

    /**
     * The TestFixture that will be written to a status.dat compatible format
     * @var TestFixture
     */
    private $fixture;

    /**
     * The content of the status.dat file that will be written
     * @var String
     */
    private $statusDat;

    /**
     * The content of the objects.cache file that will be written
     * @var String
     */
    private $objectsCache;

    /**
     * Tell this object to use the status.dat/objects.cache file combination
     * provided in $resource
     *
     * @param Array $ressource  An associative array containing the following keys:
     *                          - "status_file" : The location where to write the status.dat to
     *                          - "objects_file" : The location to write the objects cache to
     */
    public function setConnection($ressource)
    {
        $this->statusDatFile = $ressource['status_file'];
        $this->objectsCacheFile = $ressource['objects_file'];
    }

    /**
     * Insert the provided fixture into the status.dat and objects.cache files for testing
     *
     * @param TestFixture $fixture  The fixture to create status.dat and objects.cache files from
     */
    public function insert(TestFixture $fixture)
    {
        $this->fixture = $fixture;
        $this->statusDat = '# Automatically created test statusdat from fixture\n';
        $this->objectsCache = '';
        $this->insertHoststatus();
        $this->insertHosts();
        $this->insertServicestatus();
        $this->insertServices();

        $this->insertHostgroups();
        $this->insertServicegroups();
        $this->insertComments();

        file_put_contents($this->statusDatFile, $this->statusDat);
        file_put_contents($this->objectsCacheFile, $this->objectsCache);
    }

    /**
     *  Insert the host monitoring state from the provided fixture to the internal
     *  statusdat string $statusDat
     *
     */
    private function insertHoststatus()
    {
        $hosts = $this->fixture->getHosts();
        foreach ($hosts as $host) {
            $cvs = '';
            foreach ($host['customvariables'] as $name=>$var) {
                $cvs .= '_'.$name.'='.$var."\n";
            }
            $flags = $host['flags'];
            $hostStatus = str_replace(
                array(
                    '{{HOST_NAME}}', '{{TIME}}', '{{NOTIFICATIONS_ENABLED}}',
                    '{{ACKNOWLEDGED}}', '{{ACTIVE_ENABLED}}', '{{PASSIVE_ENABLED}}',
                    '{{FLAPPING}}', '{{IN_DOWNTIME}}', '{{HOST_STATUS}}','{{CVS}}')
                , array(
                    $host['name'], $flags->time, $flags->notifications, $flags->acknowledged,
                    $flags->active_checks, $flags->passive_checks, $flags->flapping,
                    $flags->in_downtime, $host['state'], $cvs
                ), StatusdatTemplates::$HOST);
            $this->statusDat .= "\n".$hostStatus;
        }
    }

    /**
     *  Insert the host object state into the internal objects.cache representation
     *  $objectsCache
     *
     */
    private function insertHosts()
    {
        $hosts = $this->fixture->getHosts();
        foreach ($hosts as $host) {
            if ($host['flags']->is_pending) {
                continue; // Pending states are not written to status.dat yet
            }
            $hostDefinition = str_replace(
                array('\t',
                    '{{HOST_NAME}}', '{{HOST_ADDRESS}}', '{{ICON_IMAGE}}',
                    '{{NOTES_URL}}', '{{ACTION_URL}}'
                ),
                array("\t",
                    $host['name'], $host['address'], $host['icon_image'],
                    $host['notes_url'], $host['action_url']
                ),
                ObjectsCacheTemplates::$HOST
            );
            $this->objectsCache .= "\n".$hostDefinition;
        }
    }

    /**
     *  Insert the service monitoring state from the provided fixture to the internal
     *  statusdat string $statusDat
     *
     */
    private function insertServicestatus()
    {
        $services = $this->fixture->getServices();
        foreach ($services as $service) {
            if ($service['flags']->is_pending) {
                continue; // Pending states are not written to status.dat yet
            }
            $cvs = '';
            foreach ($service['customvariables'] as $name=>$var) {
                $cvs .= '_'.$name.'='.$var;
            }

            $flags = $service['flags'];
            $serviceStatus = str_replace(
                array(
                    '{{HOST_NAME}}','{{SERVICE_NAME}}', '{{TIME}}', '{{NOTIFICATIONS_ENABLED}}',
                    '{{ACKNOWLEDGED}}', '{{ACTIVE_ENABLED}}', '{{PASSIVE_ENABLED}}',
                    '{{FLAPPING}}', '{{IN_DOWNTIME}}', '{{SERVICE_STATUS}}','{{CVS}}')
                , array(
                    $service['host']['name'], $service['name'], $flags->time, $flags->notifications,
                    $flags->acknowledged, $flags->active_checks, $flags->passive_checks,
                    $flags->flapping, $flags->in_downtime, $service['state'], $cvs
                ), StatusdatTemplates::$SERIVCE);



            $this->statusDat .= "\n".$serviceStatus;
        }


    }

    /**
     *  Insert the service object state into the internal objects.cache representation
     *  $objectsCache
     *
     */
    private function insertServices()
    {
        $services = $this->fixture->getServices();
        foreach ($services as $service) {
            $serviceDefinition = str_replace(
                array('\t',
                    '{{HOST_NAME}}', '{{SERVICE_NAME}}', '{{ICON_IMAGE}}',
                    '{{NOTES_URL}}', '{{ACTION_URL}}'
                ),
                array("\t",
                    $service['host']['name'], $service['name'], $service['icon_image'],
                    $service['notes_url'], $service['action_url']
                ),
                ObjectsCacheTemplates::$SERVICE
            );
            $this->objectsCache .= "\n".$serviceDefinition;
        }
    }

    /**
     * Inserts a group object into the object.cache file
     *
     * @param String    $type       The type of the group ('host' or 'service')
     * @param String    $name       The name of the group to insert
     * @param array     $members    A String array of the members names to use
     */
    private function insertGroup($type, $name, array $members)
    {
        $groupDefinition = str_replace(
            array('\t',
                '{{TYPE}}', '{{NAME}}', '{{MEMBERS}}'
            ),
            array("\t",
                $type, $name, implode(",", $members)
            ),
            ObjectsCacheTemplates::$GROUP
        );
        $this->objectsCache .= "\n".$groupDefinition;
    }

    /**
     * Insert all hostgroups from the fixtures into the objects.cache
     *
     */
    private function insertHostgroups()
    {
        $hostgroups = $this->fixture->getHostgroups();
        foreach ($hostgroups as $hostgroup) {
            $memberNames = array();
            foreach ($hostgroup["members"] as $member) {
                $memberNames[] = $member["name"];
            }
            $this->insertGroup("host", $hostgroup["name"], $memberNames);
        }
    }

    /**
     * Inserts all servicegroups from the fixtures into the objects.cache
     *
     */
    private function insertServicegroups()
    {
        $servicegroups = $this->fixture->getServicegroups();
        foreach ($servicegroups as $servicegroup) {
            $memberNames = array();
            foreach ($servicegroup["members"] as $member) {
                $memberNames[] = $member["host"]["name"];
                $memberNames[] = $member["name"];
            }
            $this->insertGroup("service", $servicegroup["name"], $memberNames);
        }
    }

    /**
     * Inserts all comments from the fixtures into the status.dat string
     * $statusDat
     *
     */
    private function insertComments()
    {
        $comments = $this->fixture->getComments();
        $commentId = 1;
        foreach($comments as $comment) {
            if (isset($comment["service"])) {
                $service = $comment["service"];
                $commentDefinition = str_replace(
                    array('{{HOST_NAME}}', '{{SERVICE_NAME}}', '{{TIME}}', '{{AUTHOR}}', '{{TEXT}}', '{{ID}}'),
                    array(
                        $service["host"]["name"], $service["name"], $service["flags"]->time,
                        $comment["author"], $comment["text"], $commentId++
                    ),
                    StatusdatTemplates::$SERVICECOMMENT
                );
            } elseif (isset($comment["host"])) {
                $host = $comment["host"];
                $commentDefinition = str_replace(
                    array('{{HOST_NAME}}', '{{TIME}}', '{{AUTHOR}}', '{{TEXT}}', '{{ID}}'),
                    array(
                        $host["name"], $host["flags"]->time,
                        $comment["author"], $comment["text"], $commentId++
                    ),
                    StatusdatTemplates::$HOSTCOMMENT
                );
            }
            $this->statusDat .= "\n".$commentDefinition;
        }
    }
}