Testing guide
=============

Testing controllers for compatibility with different monitoring datasources
---------------------------------------------------------------------------

When it comes to writing controllers, it is important that your actions and queries work on every monitoring
datasource supported by icinga2 web. For this, the monitoring module provides a test library for controllers.

The database setup for every testcase
--------------------------------------

When testing PostgreSQL and MySQL databases, the test library (normally) executes the following test procedure for every
test case:

-   Log in to the rdbms as the user icinga_unittest with password icinga_unittest
-   Use the icinga_unittest database (which must be existing)
-   Drop all tables in the icinga_unittest database (so *NEVER* run unit tests on your production system)
-   Create a new, clean database schema

If anything goes wrong during this procedure, the test will be skipped (because maybe you don't have a pgsql database, but
want to test mysql, for example)

Setting up a test user and database in MySQL
--------------------------------------------

In MySQL, it's best to create a user icinga_unittest@localhost, a database icinga_unittest and grant all privileges on
this database:

    mysql -u root -p
    mysql> CREATE USER `icinga_unittest`@`localhost` IDENTIFIED BY 'icinga_unittest';
    mysql> CREATE DATABASE `icinga_unittest`;
    mysql> GRANT ALL PRIVILEGES ON `icinga_unittest`.* TO `icinga_unittest`@`localhost`;
    mysql> FLUSH PRIVILEGES;
    mysql> quit

Setting up a test user and database in PostgreSQL
-------------------------------------------------

In PostgreSQL, you have to modify the pg_hba database if you don't have password authentication set up (which often is
the case). In this setup the icinga_unittest user is set to trust authentication on localhost, which means that no
password is queried when connecting from the local machine:

    sudo su postgres
    psql
    postgres=#  CREATE USER icinga_unittest WITH PASSWORD 'icinga_unittest';
    postgres=#  CREATE DATABASE icinga_unittest;
    postgres=#  \q
    bash$       createlang plpgsql icinga;


Writing tests for controllers
-----------------------------

When writing tests for controllers, you can subclass the MonitoringControllerTest class underneath monitoring/test/php/testlib:

    class MyTestclass extends MonitoringControllerTest
    {
        // test stuff
    }

This class handles a lot of depenendency resolving and controller mocking. In order to test your action correctly and
without side effects, the TestFixture class allows your to define and set up your faked monitoring results in the backend
you want to test:

    use Test\Monitoring\Testlib\Datasource\TestFixture;

    class MyTestclass extends MonitoringControllerTest
    {
        public function testSomething()
        {
            $fixture = new TestFixture();
            // adding a new critical, but acknowledged host
            $fixture->addHost("hostname", 1, ObjectFlags::ACKNOWLEDGED())

            // add a comment to the host (this has to be done before adding services)
            ->addComment("author", "comment text")

            // assign to hostgroup
            ->addToHostgroup("myHosts")

            // and add three services to this host
            ->addService("svc1", 0) // Service is ok
            ->addService("svc2", 1, ObjectFlags::PASSIVE) // service is warning and passive
            ->addService("svc3", 2, null, array("notes_url" => "test.html")) // critical with notes url
            ->addComment("author", "what a nice service comment") // add a comment to the service
            ->addToServicegroup("alwaysdown"); // add svc3 to servicegroup

            // Create the datasource from this fixture, here in MySQL
            $this->setupFixture($fixture, "mysql");

            // ... do the actual testing (discussed now)
        }
    }

After the call to setupFixture() your backend should be ready to be tested. Setting up the controller manually would
force you to go through the whole bootstrap. To avoid this the MonitoringControllerTest class provides a 'requireController'
method which returns the Controller for you with an already set up backend using your previously defined testdata:

    $controller = $this->requireController('MyController', 'mysql');
    // controller is now the Zend controller instance, perform an action
    $controller->myAction();
    $result = $controller->view->hosts->fetchAll();

This example assumes that the controller populates the 'host' variable in the view, so now you can assert the state of
the result according to your test plan.