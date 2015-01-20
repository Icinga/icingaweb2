# Icinga Web 2

## Table of Contents

0. [About](#about)
1. [Installation](#installation)
2. [Support](#support)
3. [Vagrant - Virtual development environment](#vagrant)

## About

**Icinga Web 2** is the next generation open source monitoring web interface, framework
and command-line interface developed by the [Icinga Project](https://www.icinga.org/), supporting Icinga 2,
Icinga Core and any other monitoring backend compatible with the Livestatus Protocol.

![Icinga Web 2](https://www.icinga.org/wp-content/uploads/2014/06/service_detail.png "Icinga Web 2")

## Installation

For installing Icinga Web 2 please read [doc/installation.md](doc/installation.md).

## Support

If you come across problems at some time, the [community support channels](https://support.icinga.org/) 
are good places to ask for advice from other users and give some in return.

For status updates check the [Icinga website](https://www.icinga.org/) and the
[Icinga Web 2 development roadmap](https://dev.icinga.org/projects/icingaweb2/roadmap).


## Vagrant

### Requirements

* Vagrant 1.2+
* Virtualbox 4.2.16+
* a fairly powerful hardware (quad core, 4gb ram, fast hdd)

> **Note**
>
> The deployment of the virtual machine is tested against Vagrant starting with version 1.2.
> Unfortunately older versions will not work.

### General

The Icinga Web 2 project ships with a Vagrant virtual machine that integrates
the source code with various services and example data in a controlled
environment. This enables developers and users to test Livestatus, status.dat,
MySQL and PostgreSQL backends as well as the LDAP authentication. All you
have to do is install Vagrant and run:

    vagrant up

> **Note**
> 
> The first boot of the vm takes a fairly long time because
> you'll download a plain CentOS base box and Vagrant will automatically
> provision the environment on the first go.

After you should be able to browse [localhost:8080/icingaweb](http://localhost:8080/icingaweb).

### Environment

**Forwarded ports**:

<table>
    <tr>
        <th>Proctocol</th>
        <th>Local port (virtual machine host)</th>
        <th>Remote port (the virtual machine)</th>
    </tr>
    <tr>
        <td>SSH</td>
        <td>2222</td>
        <td>22</td>
    </tr>
    <tr>
        <td>HTTP</td>
        <td>8080</td>
        <td>80</td>
    </tr>
</table>

**Installed packages**:

* Apache2 with PHP enabled
* PHP with MySQL and PostgreSQL libraries
* MySQL server and client software
* PostgreSQL server and client software
* [Icinga prerequisites](http://docs.icinga.org/latest/en/quickstart-idoutils.html#installpackages)
* OpenLDAP servers and clients

**Installed users and groups**:

* User icinga with group icinga and icinga-cmd
* Webserver user added to group icinga-cmd

**Installed software**:

* Icinga with IDOUtils using a MySQL database
* Icinga with IDOUtils using a PostgreSQL database
* Icinga 2

**Installed files**:

* `/usr/share/icinga/htpasswd.users` account information for logging into the Icinga classic web interface for both icinga instances
* `/usr/lib64/nagios/plugins` Monitoring Plugins for all Icinga instances

#### Icinga with IDOUtils using a MySQL database

**Installation path**: `/usr/local/icinga-mysql`

**Services**:

* `icinga-mysql`
* `ido2db-mysql`

Connect to the **icinga mysql database** using the following command:

    mysql -u icinga -p icinga icinga

Access the **Classic UI** (CGIs) via [localhost:8080/icinga-mysql](http://localhost:8080/icinga-mysql).
For **logging into** the Icinga classic web interface use user *icingaadmin* with password *icinga*.

#### Icinga with IDOUtils using a PostgreSQL database

**Installation path**: `/usr/local/icinga-pgsql`

**Services**:

* `icinga-pgsql`
* `ido2db-pgsql`

Connect to the **icinga mysql database** using the following command:

    sudo -u postgres psql -U icinga -d icinga

Access the **Classic UI** (CGIs) via [localhost:8080/icinga-pgsql](http://localhost:8080/icinga-pgsql).
For **logging into** the Icinga classic web interface use user *icingaadmin* with password *icinga*.

#### Monitoring Test Config

Test config is added to both the MySQL and PostgreSQL Icinga instance utilizing the Perl module
**Monitoring::Generator::TestConfig** to generate test config to **/usr/local/share/misc/monitoring_test_config**
which is then copied to **<instance>/etc/conf.d/test_config/**.
Configuration can be adjusted and recreated with **/usr/local/share/misc/monitoring_test_config/recreate.pl**.
**Note** that you have to run

    vagrant provision

in the host after any modification to the script just mentioned.

#### MK Livestatus

MK Livestatus is added to the Icinga installation using a MySQL database.

**Installation path**:

* `/usr/local/icinga-mysql/bin/unixcat`
* `/usr/local/icinga-mysql/lib/mk-livestatus/livecheck`
* `/usr/local/icinga-mysql/lib/mk-livestatus/livestatus.o`
* `/usr/local/icinga-mysql/etc/modules/mk-livestatus.cfg`
* `/usr/local/icinga-mysql/var/rw/live`

**Example usage**:

    echo "GET hosts" | /usr/local/icinga-mysql/bin/unixcat /usr/local/icinga-mysql/var/rw/live

#### LDAP example data

The environment includes a openldap server with example data. *Domain* suffix is **dc=icinga,dc=org**.
Administrator (*rootDN*) of the slapd configuration database is **cn=admin,cn=config** and the
administrator (*rootDN*) of our database instance is **cn=admin,dc=icinga,dc=org**. Both share
the *password* `admin`.

Examples to query the slapd configuration database:

    ldapsearch -x -W -LLL -D cn=admin,cn=config -b cn=config dn
    ldapsearch -Y EXTERNAL -H ldapi:/// -LLL -b cn=config dn

Examples to query our database instance:

    ldapsearch -x -W -LLL -D cn=admin,dc=icinga,dc=org -b dc=icinga,dc=org dn
    ldapsearch -Y EXTERNAL -H ldapi:/// -LLL -b dc=icinga,dc=org dn

This is what the **dc=icinga,dc=org** *DIT* looks like:

> dn: dc=icinga,dc=org
>
> dn: ou=people,dc=icinga,dc=org
>
> dn: ou=groups,dc=icinga,dc=org
>
> dn: cn=Users,ou=groups,dc=icinga,dc=org
> cn: Users
> uniqueMember: cn=Jon Doe,ou=people,dc=icinga,dc=org
> uniqueMember: cn=Jane Smith,ou=people,dc=icinga,dc=org
> uniqueMember: cn=John Q. Public,ou=people,dc=icinga,dc=org
> uniqueMember: cn=Richard Roe,ou=people,dc=icinga,dc=org
>
> dn: cn=John Doe,ou=people,dc=icinga,dc=org
> cn: John Doe
> uid: jdoe
>
> dn: cn=Jane Smith,ou=people,dc=icinga,dc=org
> cn: Jane Smith
> uid: jsmith
>
> dn: cn=John Q. Public,ou=people,dc=icinga,dc=org
> cn: John Q. Public
> uid: jqpublic
>
> dn: cn=Richard Roe,ou=people,dc=icinga,dc=org
> cn: Richard Roe
> uid: rroe

All users share the password `password`.

#### Testing the code

All software required to run tests is installed in the virtual machine.
In order to run all tests you have to execute the following commands:

    vagrant ssh -c /vagrant/test/php/runtests
    vagrant ssh -c /vagrant/test/php/checkswag
    vagrant ssh -c /vagrant/test/js/runtests
    vagrant ssh -c /vagrant/test/js/checkswag
    vagrant ssh -c /vagrant/test/frontend/runtests

`runtests` will execute unit and regression tests and `checkswag` will report
code style issues.

#### Icinga 2

Installed from the Icinga [snapshot package repository](http://packages.icinga.org/epel/).
The configuration is located in `/etc/icinga2`.

**Example usage**:

    /etc/init.d/icinga2 (start|stop|restart|reload)


## Log into Icinga Web 2

If you've configure LDAP as authentication backend (which is the default) use the following login credentials:

> **Username**: jdoe
> **Password**: password

Have a look at [LDAP example data](#ldap example data) for more accounts.

Using MySQL as backend:

> **Username**: icingaadmin
> **Password**: icinga

