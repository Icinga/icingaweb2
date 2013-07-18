<?php
/**
 * Created by JetBrains PhpStorm.
 * User: moja
 * Date: 7/17/13
 * Time: 10:18 AM
 * To change this template use File | Settings | File Templates.
 */

namespace Test\Monitoring\Testlib\Datasource\Strategies;
use Test\Monitoring\Testlib\DataSource\schemes\ObjectsCacheTemplates;
use \Test\Monitoring\Testlib\DataSource\TestFixture;
use \Test\Monitoring\Testlib\DataSource\schemes\StatusdatTemplates;

require_once(dirname(__FILE__).'/../schemes/ObjectsCacheTemplates.php');
require_once(dirname(__FILE__).'/../schemes/StatusdatTemplates.php');

class StatusdatInsertionStrategy implements InsertionStrategy {

    private $statusDatFile;
    private $objectsCacheFile;
    private $fixture;
    private $statusDat;
    private $objectsCache;

    public function setConnection($ressource)
    {
        $this->statusDatFile = $ressource['status_file'];
        $this->objectsCacheFile = $ressource['objects_file'];
    }

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

    private function insertGroup($type, $name, array $members)
    {
        $groupDefinition = str_replace(
            array('\t',
                '{{TYPE}}', '{{NAME}}', '{{MEMBERS}}'
            ),
            array("\t",
                'host', $name, implode(",", $members)
            ),
            ObjectsCacheTemplates::$GROUP
        );
        $this->objectsCache .= "\n".$groupDefinition;
    }

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

    private function insertServicegroups()
    {
        $servicegroups = $this->fixture->getServicegroups();
        foreach ($servicegroups as $servicegroup) {
            $memberNames = array();
            foreach ($servicegroup["members"] as $member) {
                $memberNames[] = $member["host"]["name"];
                $memberNames[] = $member["name"];
            }
            $this->insertGroup("service", $serviegroup["name"], $memberNames);
        }
    }

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