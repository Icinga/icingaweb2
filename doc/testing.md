# Testing guide


## Testing controllers for compatibility with different monitoring datasources


When it comes to writing controllers, it is important that your actions and queries work on every monitoring
datasource supported by icinga2 web. For this, the monitoring module provides a test library for controllers.

## The database setup for every testcase

When testing PostgreSQL and MySQL databases, the test library (normally) executes the following test procedure for every
test case:

-   Log in to the rdbms as the user icinga_unittest with password icinga_unittest
-   Use the icinga_unittest database (which must be existing)
-   Drop all tables in the icinga_unittest database (so *NEVER* run unit tests on your production system)
-   Create a new, clean database schema

If anything goes wrong during this procedure, the test will be skipped (because maybe you don't have a pgsql database, but
want to test mysql, for example)

## Setting up a test user and database in MySQL

In MySQL, it's best to create a user icinga_unittest@localhost, a database icinga_unittest and grant all privileges on
this database:

    mysql -u root -p
    mysql> CREATE USER `icinga_unittest`@`localhost` IDENTIFIED BY 'icinga_unittest';
    mysql> CREATE DATABASE `icinga_unittest`;
    mysql> GRANT ALL PRIVILEGES ON `icinga_unittest`.* TO `icinga_unittest`@`localhost`;
    mysql> FLUSH PRIVILEGES;
    mysql> quit

## Setting up a test user and database in PostgreSQL

In PostgreSQL, you have to modify the pg_hba database if you don't have password authentication set up (which often is
the case). In this setup the icinga_unittest user is set to trust authentication on localhost, which means that no
password is queried when connecting from the local machine:

    sudo su postgres
    psql
    postgres=#  CREATE USER icinga_unittest WITH PASSWORD 'icinga_unittest';
    postgres=#  CREATE DATABASE icinga_unittest;
    postgres=#  \q
    bash$       createlang plpgsql icinga;

## Writing tests for icinga

Icinga has it's own base test which lets you easily require libraries, testing database and form functionality. The class resides in
library/Icinga/Test. If you write a test, just subclass BaseTestCase.

### Writing database tests

The base test uses the PHPUnit dataProvider annotation system to create database connections. Typically a
database test looks like this:

        /**
         * @dataProvider    mysqlDb
         * @param           Icinga\Data\Db\DbConnection    $mysqlDb
         */
        public function testSomethingWithMySql($mysqlDb)
        {
            $this->setupDbProvider($mysqlDb); // Drops everything from existing database

             // Load a dump file into database
             $this->loadSql($mysqlDb, BaseTestCase::$etcDir . '/etc/schema/mydump.mysql.sql');

             // Test your code
        }

Available data providers are: mysqlDb, pgsqlDb, oracleDb. The test will be skipped if a provider
could not be initialized.

### Write form tests

BaseTestCase holds method to require form libraries and create form classes based on class names.

        public function testShowModifiedOrder()
        {
            $this->requireFormLibraries();
            $form = $this->createForm(
                'Icinga\Form\Config\AuthenticationForm',
                array(
                    'priority' => 'test-ldap,test-db'
                )
            );

            // Testing your code
        }

The second parameter of createForm() can be omitted. You can set initial post request data as
an array if needed.
