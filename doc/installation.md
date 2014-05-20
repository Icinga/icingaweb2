
# Installation

## Requirements

* Apache2 with PHP >= 5.3.0 enabled
* PHP Zend Framework
* PHP with MySQL or PostgreSQL libraries
* MySQL or PostgreSQL server and client software 
* Icinga 1.x or Icinga 2 as backend providers

RHEL/CentOS requires the EPEL repository enabled (which provides the `php-ZendFramework`
package). OpenSUSE requires the [server monitoring](https://build.opensuse.org/project/show/server:monitoring) repository (which provides the `php5-ZendFramework` package) enabled.

## configure && make

### Basic installation

If you like to configurea and install icinga2-web from the command line or 
if you want to create packages, configure and make is the best choice for installation.

    ./configure && make install && make install-apache2-config

will install the application to the default target (/usr/local/icinga2-web). Also
an apache configuration entry is added to your apache server, so you should restart
your web server according to your systems configuration.

### Installation directory

If you want to install the application to a different directory, use the --prefix flag in your 
configure call:

    ./configure --prefix=/my/target/directory


### Authentication

By default, icinga2-web will be installed to authenticate againts its internal database,
but you can configure it to use ldap-authentication by adding the `--with-ldap-authentication` 
flag. You must provide the authentication details for your ldap server by using the --with-ldap-* flags.
To see a full list of the flags, call `./configure --help`

### Icinga backend

The default option for icinga2web is to configure all icinga backends with the default settings (for example
/usr/local/icinga/ as the icinga directory) but only enable statusdat. To use a different backend,
call `--with-icinga-backend=` and provide ido, livestatus or statusdat as an option. To further configure
your backend, take a look at the various options described in `./configure --help` 

### Databases

It is required to set up all used Databases correctly, which basically means to create all needed user accounts and to
create all database tables. You will find the installation guides for the different databases in the sections below:

*IMPORTANT*: Select a secure password instead of "icingaweb" and alter the config/authentication.ini accordingly.


#### MySQL

1. Create the user and the database


    mysql -u root -p
    mysql> CREATE USER `icingaweb`@`localhost` IDENTIFIED BY 'icingaweb';
    mysql> CREATE DATABASE `icingaweb`;
    mysql> GRANT ALL PRIVILEGES ON `icingaweb`.* TO `icingaweb`@`localhost`;
    mysql> FLUSH PRIVILEGES;
    mysql> quit


2. Create all tables (You need to be in the icinga2-web folder)

> **Note**
>
> RPM packages install the schema into /usr/share/doc/icingaweb-&lt;version&gt;/schema

   bash$  mysql -u root -p icingaweb < etc/schema/accounts.mysql.sql
   bash$  mysql -u root -p icingaweb < etc/schema/preferences.mysql.sql


#### PostgreSQL

1. Create the user and the database


    sudo su postgres
    psql
    postgres=#  CREATE USER icingaweb WITH PASSWORD 'icingaweb';
    postgres=#  CREATE DATABASE icingaweb;
    postgres=#  \q


2. Enable trust authentication on localhost

Add the following lines to your pg_hba.conf (etc/postgresql/X.x/main/pg_hba.conf under debian, /var/lib/pgsql/data/pg_hba.conf for Redhat/Fedora)
to enable trust authentication for the icingaweb user when connecting from the localhost.

    local   icingaweb      icingaweb                            trust
    host    icingaweb      icingaweb      127.0.0.1/32          trust
    host    icingaweb      icingaweb      ::1/128               trust

And restart your database ('service postgresql restart' or '/etc/init.d/postgresql-X.x reload' while being root)


3. Create all tables (You need to be in the icinga2-web folder)

> **Note**
>
> RPM packages install the schema into /usr/share/doc/icingaweb-&lt;version&gt;/schema

    bash$  psql -U icingaweb -a -f etc/schema/accounts.pgsql.sql
    bash$  psql -U icingaweb -a -f etc/schema/preferences.pgsql.sql



Quick and Dirty
----------------

tdb.
