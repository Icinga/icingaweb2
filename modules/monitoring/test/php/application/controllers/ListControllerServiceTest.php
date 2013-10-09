<?php

namespace Test\Monitoring\Application\Controllers\ListController;

require_once(dirname(__FILE__).'/../../testlib/MonitoringControllerTest.php');

use Test\Monitoring\Testlib\MonitoringControllerTest;
use Test\Monitoring\Testlib\Datasource\TestFixture;
use Test\Monitoring\Testlib\Datasource\ObjectFlags;

class ListControllerServiceMySQLTest  extends MonitoringControllerTest
{

    public function testServiceListMySQL()
    {
        $this->executeServiceListTestFor("mysql");
    }

    public function testServiceListPgSQL()
    {
        $this->executeServiceListTestFor("pgsql");
    }

//    public function testServiceListStatusdat()
//    {
//        $this->executeServiceListTestFor("statusdat");
//    }

    public function executeServiceListTestFor($backend)
    {
        date_default_timezone_set('UTC');
        $checkTime = time()-2000;
        $fixture = new TestFixture();
        $fixture->addHost('host1', 0)->
                addService("svc1", 0, new ObjectFlags(2000), array(
                    "notes_url" => "notes.url",
                    "action_url" => "action.url",
                    "icon_image" => "svcIcon.png"
                ))->
                addService("svcDown", 2) -> addComment("author", "Comment text")->
                addService("svcFlapping", 1, ObjectFlags::FLAPPING())->addToServicegroup("Warning")->
                addService("svcNotifDisabled", 2, ObjectFlags::DISABLE_NOTIFICATIONS())->
                addService("svcPending", 0, ObjectFlags::PENDING());
        $fixture->addHost('host2', 1)->
                addService("svcPassive", 1, ObjectFlags::PASSIVE_ONLY())->addToServicegroup("Warning")->
                addService("svcDisabled", 1, ObjectFlags::DISABLED())->addToServicegroup("Warning")->
                addService("svcDowntime", 2, ObjectFlags::IN_DOWNTIME())->
                addService("svcAcknowledged", 1, ObjectFlags::ACKNOWLEDGED())->addToServicegroup("Warning");
        try {
            $this->setupFixture($fixture, $backend);
        } catch (\PDOException $e) {
            echo $e->getMessage();
            $this->markTestSkipped('Could not setup fixture for backends '.$backend.' :'.$e->getMessage());
            return null;
        }

        $controller = $this->requireController('ListController', $backend);
        $controller->servicesAction();

        $result = $controller->view->services;

        $this->assertEquals(9, $result->getTotalItemCount(), "Testing for correct service count");
        $result = $result->getAdapter()->getItems(0,1);
        $this->assertEquals("notes.url", $result[0]->service_notes_url, "Testing for correct notes_url");
        $this->assertEquals("action.url", $result[0]->service_action_url, "Testing for correct action_url");
        $this->assertEquals(0, $result[0]->service_state, "Testing for correct Service state");
    }

}
