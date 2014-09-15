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

## Webinterface Login

The default credentials using the internal MySQL database are
`icingaadmin:icinga`

## Support

Please use one of the listed support channels at https://support.icinga.org


## Internal DB Setup

Decide whether to use MySQL or PostgreSQL.

### MySQL

    mysql -u root -p
        CREATE USER `icingaweb`@`localhost` IDENTIFIED BY 'icingaweb';
        CREATE DATABASE `icingaweb`;
        GRANT ALL PRIVILEGES ON `icingaweb`.* TO `icingaweb`@`localhost`;
        FLUSH PRIVILEGES;
        quit

    mysql -u root -p icingaweb < /usr/share/doc/icingaweb2*/schema/accounts.mysql.sql
    mysql -u root -p icingaweb < /usr/share/doc/icingaweb2*/schema/preferences.mysql.sql

### PostgreSQL

    sudo su postgres
    psql
    postgres=#  CREATE USER icingaweb WITH PASSWORD 'icingaweb';
    postgres=#  CREATE DATABASE icingaweb;
    postgres=#  \q

Add the `cingaweb` user for trusted authentication to your `pg_hba.conf` file
in `/var/lib/pgsql/data/pg_hba.conf` and restart the PostgreSQL server.

    local   icingaweb      icingaweb                            trust
    host    icingaweb      icingaweb      127.0.0.1/32          trust
    host    icingaweb      icingaweb      ::1/128               trust

Now install the `icingaweb` schema

    bash$  psql -U icingaweb -a -f /usr/share/doc/icingaweb2*/schema/accounts.pgsql.sql
    bash$  psql -U icingaweb -a -f /usr/share/doc/icingaweb2*/schema/preferences.pgsql.sql


## Configuration

### Module Configuration

The monitoring module is enabled by default.

### Backend configuration

`/etc/icingaweb2/resources.ini` contains the database backend information.
By default the Icinga 2 DB IDO is used by the monitoring module in
`/etc/icingaweb2/modules/monitoring/backends.ini`

The external command pipe is required for sending commands
and configured for Icinga 2 in
`/etc/icingaweb2/modules/monitoring/instances.ini`

### Authentication configuration

The `/etc/icingaweb2/authentication.ini` file uses the internal database as
default. This requires the database being installed properly before
allowing users to login via web console.
