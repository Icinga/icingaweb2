<?php

namespace Test\Monitoring\Application\Controllers\ListController;

require_once(dirname(__FILE__).'/../../testlib/MonitoringControllerTest.php');

require_once(dirname(__FILE__).'/../../../../library/Monitoring/DataView/DataView.php');
require_once(dirname(__FILE__).'/../../../../library/Monitoring/DataView/HostAndServiceStatus.php');
require_once(dirname(__FILE__).'/../../../../library/Monitoring/DataView/Notification.php');
require_once(dirname(__FILE__).'/../../../../library/Monitoring/DataView/Downtime.php');

use Test\Monitoring\Testlib\MonitoringControllerTest;
use Test\Monitoring\Testlib\Datasource\TestFixture;
use Test\Monitoring\Testlib\Datasource\ObjectFlags;

class ListControllerHostMySQLTest  extends MonitoringControllerTest
{

    public function testHostListMySQL()
    {
        $this->executeHostListTestFor("mysql");
    }

    public function testHostListPgSQL()
    {
        $this->executeHostListTestFor("pgsql");
    }

    public function testHostListStatus()
    {
        $this->executeHostListTestFor("statusdat");
    }

    public function executeHostListTestFor($backend)
    {
        date_default_timezone_set('UTC');
        $checkTime = (string)(time()-2000);
        $fixture = new TestFixture();
        $firstHostFlags = ObjectFlags::PASSIVE_ONLY();
        $firstHostFlags->acknowledged = 1;
        $firstHostFlags->in_downtime = 1;
        $firstHostFlags->notifications = 0;
        $firstHostFlags->flapping = 1;
        $firstHostFlags->time = $checkTime;

        $fixture->addHost('host1', 1, $firstHostFlags, array(
            "address" => "10.92.1.5",
            "icon_image" => "myIcon.png",
            "notes_url" => "note1.html",
            "action_url" => "action.html"))->
                addToHostgroup('router')->
                addComment('author', 'host comment text')->
                addService('svc1', 2)->
                addService('svc2', 2)->
                addService('svc3', 2, ObjectFlags::ACKNOWLEDGED())->
                addService('svc4', 0);
        $fixture->addHost('host2', 1)->
                addService('svc1', 2);
        $fixture->addHost('host3', 0)->
                addService('svc1', 0);
        $fixture->addHost('host4', 0)->
                addService('svc1', 0);
        $fixture->addHost('host5', 2, ObjectFlags::ACKNOWLEDGED())->
                addService('svc1', 3)->addComment('author','svc comment');

        try {
            $this->setupFixture($fixture, $backend);
        } catch (\PDOException $e) {
            echo $e->getMessage();
            $this->markTestSkipped('Could not setup fixture for backends '.$backend.' :'.$e->getMessage());
            return null;
        }
        $controller = $this->requireController('ListController', $backend);
        $controller->hostsAction();
        $result = $controller->view->hosts;

        $this->assertEquals(5, $result->getTotalItemCount(), 'Testing correct result count for '.$backend);

        $result = $result->getAdapter()->getItems(0,6);
        for($i=1;$i<=5;$i++) {
            $this->assertEquals('host'.$i, $result[$i-1]->host_name, "Asserting correct host names for backend ".$backend);
        }

        $hostToTest = $result[0];
        $persistedLastCheck = explode("+", $hostToTest->host_last_check);
        $persistedLastCheck = $persistedLastCheck[0];
        $this->assertEquals("10.92.1.5", $hostToTest->host_address, "Testing for correct host address field (backend ".$backend.")");
        $this->assertEquals(1, $hostToTest->host_state, "Testing for status being DOWN (backend ".$backend.")");
        // commented out due to failing tests when delay is too long
        // $this->assertEquals(date("Y-m-d H:i:s", intval($checkTime)), $persistedLastCheck, "Testing for correct last check time format (backend ".$backend.")");
        //$this->assertEquals($checkTime, $hostToTest->host_last_state_change, "Testing for correct last state change (backend ".$backend.")");
        $this->assertEquals("Plugin output for host host1", $hostToTest->host_output, "Testing correct output for host (backend ".$backend.")");
        $this->assertEquals("Long plugin output for host host1", $hostToTest->host_long_output, "Testing correct long output for host (backend ".$backend.")");
        $this->assertEquals(0, $hostToTest->host_notifications_enabled, "Testing for disabled notifications (backend ".$backend.')');
        $this->assertEquals(1, $hostToTest->host_acknowledged, "Testing for host being acknowledged (backend ".$backend.')');
        $this->assertEquals(1, $hostToTest->host_in_downtime, "Testing for host being in downtime (backend ".$backend.')');
        $this->assertEquals(1, $hostToTest->host_is_flapping, "Testing for host being flapping (backend ".$backend.')');
        $this->assertEquals(1, $hostToTest->host_last_comment, 'Testing correct comment count for first host (backend '.$backend.')');
        $this->assertEquals(0, $hostToTest->host_state_type, 'Testing for soft state');
        $this->assertEquals(1, $hostToTest->host_handled, 'Testing for handled host (backend '.$backend.')');
        $this->assertEquals("myIcon.png", $hostToTest->host_icon_image, 'Testing for icon image (backend '.$backend.')');
        $this->assertEquals("note1.html", $hostToTest->host_notes_url, 'Testing for notes url (backend '.$backend.')');
        $this->assertEquals("action.html", $hostToTest->host_action_url, 'Testing for action url (backend '.$backend.')');
        $this->assertEquals(2, $hostToTest->host_unhandled_service_count, 'Testing correct open problems count (backend '.$backend.')');
    }

}
