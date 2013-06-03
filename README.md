# Icinga 2 Web

## Table of Contents

1. [Vagrant - Virtual development environment](#vagrant)

### Vagrant

The Icinga 2 Web project ships with a Vagrant virtual machine that integrates
the source code with various services and example data in a controlled
environment. This enables developers and users to test Livestatus, status.dat,
MySQL and PostgreSQL backends as well as the LDAP authentication. All you
have to do is install Vagrant and run:

    vagrant up

After you should be able to browse [localhost:8080/icinga2-web](http://localhost:8080/icinga2-web).

#### Environment 

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

* Apache2 with PHP library
* PHP with MySQL libray
* MySQL server and client software
* PostgreSQL server and client software
* [Icinga prerequisites](http://docs.icinga.org/latest/en/quickstart-idoutils.html#installpackages)

**Installed users and groups**:

* User icinga with group icinga and icinga-cmd
* Webserver user added to group icinga-cmd

**Installed files**:

* `/usr/share/icinga/htpasswd.users` account information for logging into the Icinga classic web interface for both instances

**Installed software**:

* Icinga 1.9.1 with IDOUtils using a MySQL database
* Icinga 1.9.1 with IDOUtils using a PostgreSQL database

##### Icinga with IDOUtils using a MySQL database

**Installation path**: `/usr/local/icinga-mysql`

**Services**:

* `icinga-mysql`
* `ido2db-mysql`

Connect to the **icinga mysql database** using the following command:

    mysql -u icinga -p icinga icinga

Access the **Classic UI** (CGIs) via [localhost:8080/icinga-mysql](http://localhost:8080/icinga-mysql).
For logging into the Icinga classic web interface use user *icingaadmin* with password *icinga*.
