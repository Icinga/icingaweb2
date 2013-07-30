# Test organization

## Testfolders

Tests for the application can be found underneath the test folder:

    test/
        php/            PHPUnit tests for backend code
        js/             mocha tests for JavaScript frontend code unittests
        frontend/       Integration tests for the frontend using casperjs

The same structure applies for modules, which also contain a toplevel test folder and suitable subtests. When you fix
a bug and write a regression test for it, put the test in the 'regression' and name it %DESCRIPTION%%TicketNumber% (myBug1234.js)

## Running tests

The tests can be run in the specific folder using the runtests script.

Running PHP tests example:

    cd test/php
    ./runtests

In this case, all application and all module tests will be executed. The testrunners also support additional flags, which
affect they way the test is executed:

    Options:
      -h, --help            show this help message and exit
      -b, --build           Enable reporting.
      -v, --verbose         Be more verbose.
      -i PATTERN, --include=PATTERN
                            Include only specific files/test cases.
      -V, --vagrant         Run in vagrant VM

Some tests also support the --exclude method, it's best to use the --help option to see which flags are supported.


## Setting up databases

Despite running most of the tests should work out of the box, a few specific cases require some setup.
At this moment, only database tests require additional setup and expect an icinga_unittest user with an icinga_unittest
database to exist and have rights in your database.

### The database test procedure

When testing PostgreSQL and MySQL databases, the test library (normally) executes the following test procedure for every
test case:

-   Log in to the rdbms as the user icinga_unittest with password icinga_unittest
-   Use the icinga_unittest database (which must be existing)
-   **Drop all tables** in the icinga_unittest database
-   Create a new, clean database schema

If anything goes wrong during this procedure, the test will be skipped (because maybe you don't have a pgsql database, but
want to test mysql, for example).

### Setting up a test user and database in MySQL

In MySQL, it's best to create a user icinga_unittest@localhost, a database icinga_unittest and grant all privileges on
this database:

    mysql -u root -p
    mysql> CREATE USER `icinga_unittest`@`localhost` IDENTIFIED BY 'icinga_unittest';
    mysql> CREATE DATABASE `icinga_unittest`;
    mysql> GRANT ALL PRIVILEGES ON `icinga_unittest`.* TO `icinga_unittest`@`localhost`;
    mysql> FLUSH PRIVILEGES;
    mysql> quit

### Setting up a test user and database in PostgreSQL

In PostgreSQL, you have to modify the pg_hba database if you don't have password authentication set up (which often is
the case). In this setup the icinga_unittest user is set to trust authentication on localhost, which means that no
password is queried when connecting from the local machine:

    sudo su postgres
    psql
    postgres=#  CREATE USER icinga_unittest WITH PASSWORD 'icinga_unittest';
    postgres=#  CREATE DATABASE icinga_unittest;
    postgres=#  \q
    bash$       createlang plpgsql icinga;


Add the following lines to your pg_hba.conf (etc/postgresql/X.x/main/pg_hba.conf under debian, /var/lib/pgsql/data/pg_hba.conf for Redhat/Fedora)
to enable trust authentication for the icingaweb user when connecting from the localhost.

    local   icinga_unittest      icinga_unittest                            trust
    host    icinga_unittest      icinga_unittest      127.0.0.1/32          trust
    host    icinga_unittest      icinga_unittest      ::1/128               trust

