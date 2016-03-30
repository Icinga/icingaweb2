# Vagrant

## Requirements

* Vagrant &gt;= version 1.5
* VirtualBox or Parallels Desktop

> **Note:** The deployment of the virtual machine is tested against Vagrant starting with version 1.5.
> Unfortunately older versions will not work.

Parallels requires the additional provider plugin
[vagrant-paralells](http://parallels.github.io/vagrant-parallels/docs/) to be installed:

    $ vagrant plugin install vagrant-parallels

## General

The Icinga Web 2 project ships with a Vagrant virtual machine that integrates
the source code with various services and example data in a controlled
environment. This enables developers and users to test Livestatus,
MySQL and PostgreSQL backends as well as the LDAP authentication. All you
have to do is install Vagrant and run:

````
vagrant up
````

> **Note:** The first boot of the vm takes a fairly long time because
> you'll download a plain CentOS base box and Vagrant will automatically
> provision the environment on the first go.

After you should be able to browse [localhost:8080/icingaweb2](http://localhost:8080/icingaweb2).

## Log into Icinga Web 2

Both LDAP and a MySQL are configured as authentication backend. Please use one of the following login credentials:

> LDAP:
>> **Username**: `jdoe`

>> **Password**: `password`

>MySQL:
>> **Username**: `icingaadmin`

>> **Password**: `icinga`



## Testing the Source Code

All software required to run tests is installed in the virtual machine.
In order to run all tests you have to execute the following command:

````
vagrant ssh -c "icingacli test php unit"
````
