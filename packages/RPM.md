# Icinga Web 2 README for RPM Packages

This file will describe how to install Icinga Web 2 from an RPM
package (RHEL/CentOS/Fedora, SLES/OpenSUSE).

## Requirements

* EPEL/OBS Repository for Zend Framework
* Apache 2.2+
* PHP 5.3+, Zend Framework, PHP PDO MySQL/PostgreSQL, PHP LDAP (optional)
* MySQL or PostgreSQL for internal DB
* Icinga 1.x or 2.x providing an IDO database (default: `icinga`)
* Icinga 1.x or 2.x providing an external command pipe (default: `icinga2.cmd`)

### SELinux

Disabled SELinux for sending commands via external command pipe
provided by Icinga (2) Core.

    setenforce 0

## Webserver Configuration

Can be generated using the following local icingacli command:

    /usr/share/icingaweb2/bin/icingacli setup config webserver apache

Pipe the output into `/etc/httpd/conf.d/icingaweb2.conf` or similar,
if not already existing.

## Setup Wizard

Navigate to `/icingaweb/setup` and follow the on-screen instructions.


## Support

Please use one of the listed support channels at https://support.icinga.com


## Manual Setup

### Internal DB Setup

Decide whether to use MySQL or PostgreSQL.

#### MySQL

    mysql -u root -p
        CREATE USER `icingaweb`@`localhost` IDENTIFIED BY 'icingaweb';
        CREATE DATABASE `icingaweb`;
        GRANT ALL PRIVILEGES ON `icingaweb`.* TO `icingaweb`@`localhost`;
        FLUSH PRIVILEGES;
        quit

    mysql -u root -p icingaweb < /usr/share/doc/icingaweb2*/schema/mysql.schema..sql

#### PostgreSQL

    sudo su postgres
    psql
    postgres=#  CREATE USER icingaweb WITH PASSWORD 'icingaweb';
    postgres=#  CREATE DATABASE icingaweb;
    postgres=#  \q

Add the `icingaweb` user for trusted authentication to your `pg_hba.conf` file
in `/var/lib/pgsql/data/pg_hba.conf` and restart the PostgreSQL server.

    local   icingaweb      icingaweb                            trust
    host    icingaweb      icingaweb      127.0.0.1/32          trust
    host    icingaweb      icingaweb      ::1/128               trust

Now install the `icingaweb` schema

    bash$  psql -U icingaweb -a -f /usr/share/doc/icingaweb2*/schema/pgsql.schema.sql


### Configuration

#### Module Configuration

The monitoring module is enabled by default.

#### Backend configuration

`/etc/icingaweb2/resources.ini` contains the database backend information.
By default the Icinga 2 DB IDO is used by the monitoring module in
`/etc/icingaweb2/modules/monitoring/backends.ini`

The external command pipe is required for sending commands
and configured for Icinga 2 in
`/etc/icingaweb2/modules/monitoring/commandtransports.ini`

#### Authentication configuration

The `/etc/icingaweb2/authentication.ini` file uses the internal database as
default. This requires the database being installed properly before
allowing users to login via web console.

#### Default User

When not using the default setup wizard, you can generate a secure password hash with openssl
and insert that manually like so:

    openssl passwd -1 "yoursecurepassword"

    mysql -uicingaweb -p icingaweb

    mysql> INSERT INTO icingaweb_user (name, active, password_hash) VALUES ('icingaadmin', 1, '$yoursecurepassword_hash');

