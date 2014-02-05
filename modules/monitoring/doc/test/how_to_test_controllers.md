# Testing controllers with different backends

Icingaweb's monitoring controllers support a variety of different backends (IDO, Statusdat, Livestatus) and make it
therefore hard to test for every backend. In order to make life a little bit easier, Test-Fixtures allow you to setup
a backend with specific monitoring data and test it afterwards by running the controller's action.

## Example

It's best to subclass MonitoringControllerTest (found in modules/monitoring/test/testlib), as this handles depency resoultion
and setup for you:


    // assume our test is underneath the test/application/controllers folder in the monitoring module
    require_once(dirname(__FILE__).'/../../testlib/MonitoringControllerTest.php');

    use Test\Monitoring\Testlib\MonitoringControllerTest;
    use Test\Monitoring\Testlib\Datasource\TestFixture;
    use Test\Monitoring\Testlib\Datasource\ObjectFlags;

    class MyControllerTest extends MonitoringControllerTest
    {
        public function testSomething()
        {
            // Create a test fixture
            $fixture = new TestFixture()
            $fixture->addHost('host', 0) // Add a host with state OK
                ->addToHostgroup('myHosts') // Add host to hostgroup
                ->addService('svc1', 1) // Add a warning service 'svc1' underneath host
                    ->addToServiceGroup('svc_warning') // add this service to a servicegroup svc_warning
                ->addService('svc2', 2, ObjectFlags::ACKNOWLEDGED()) // Add a critical, but acknowledged service to this host
                ->addService(
                    'svc3',
                    1,
                    new ObjectFlags(),
                    array("customvariables" =>
                        array("customer" => "myCustomer")
                    )
                ); // add a warning service with a customvariable

            $this->setupFixture($fixture, "mysql"); // setup the fixture for MySQL, so the backend is populated with the data set above
            // backends can be mysql, pgsql, statusdat (and in the future livestatus)
            $controller = $this->requireController('MyController', 'mysql'); // request a controller with the given backend injected
            $controller->myAction(); // controller is now the Zend controller instance, perform an action
            $result = $controller->view->hosts->fetchAll(); // get the result of the query
            // and assert stuff
            $this->assertEquals(1, count($result), "Asserting one host being retrieved in the controller");
        }
    }

## The Test-fixture API

In order to populate your backend with specific monitoring objects, you have to create a TestFixture class. This class
allows you to setup the monitoring objects in a backend independent way using a few methods:

### TestFixture::addHost($name, $state, [ObjectFlags $flags], [array $properties])

The addHost method adds a host with the name $name and the status $state (0-2) to your testfixture. When no ObjectFlags
object is provided, the default flags are used (not flapping, notifications enabled, active and passive enabled, not
acknowledged, not in downtime and not pending). The $properties array can contain additional settings like 'address',
 an 'customvariables' array, 'notes_url', 'action_url' or 'icon_image'.

Subsequent addToHostgroup and addService calls will affect this host (so the service will be added to this host)

### TestFixture::addService($name, $state, [ObjectFlags $flags], [array $properties])

The addHost method adds a service with the name $name and the status $state (0-3) to your testfixture. When no ObjectFlags
object is provided, the default flags are used (not flapping, notifications enabled, active and passive enabled, not
acknowledged, not in downtime and not pending). The $properties array can contain additional settings like an
'customvariables' array, 'notes_url', 'action_url' or 'icon_image'.

Subsequent addToServicegroup calls will affect this service.


### ObjectFlags

The Objectflags object encapsulates the following monitoring states with the following default values:

    public $flapping = 0;               // Whether this host is flapping
    public $notifications = 1;          // Whether notifications are enabled
    public $active_checks = 1;          // Whether actice checks are enabled
    public $passive_checks = 1;         // Whether passive checks are enabled
    public $acknowledged = 0;           // Whether this object has been acknowledged
    public $in_downtime = 0;            // Whether this object is in a scheduled downtime
    public $is_pending = 0;             // Whether this object is currently pending
    public $time = time();              // The last check and statechange time

ObjectFlags can either be created using new ObjectFlags([$ageInSeconds]) and directly modify the attributes or by
calling one of the following factory methods:


    ObjectFlags::FLAPPING()
    ObjectFlags::PENDING()
    ObjectFlags::DISABLE_NOTIFICATIONS()
    ObjectFlags::PASSIVE_ONLY()
    ObjectFlags::ACTIVE_ONLY()
    ObjectFlags::DISABLED() {
    ObjectFlags::ACKNOWLEDGED()
    ObjectFlags::IN_DOWNTIME()
