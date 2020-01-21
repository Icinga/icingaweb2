# Vagrant <a id="vagrant"></a>

This chapter explains how to setup the development environment
for Icinga Web 2 inside a Vagrant VM.

If you are looking for a demo setup, please use the official
[Icinga Vagrant boxes](https://github.com/icinga/icinga-vagrant)
instead.

## <a id="vagrant-requirements"></a>Requirements

* Vagrant &gt;= version 1.5
* VirtualBox or Parallels Desktop

Parallels requires the additional provider plugin
[vagrant-paralells](https://parallels.github.io/vagrant-parallels/docs/) to be installed:

```
$ vagrant plugin install vagrant-parallels
```

## <a id="vagrant-general"></a>General

The Icinga Web 2 project ships with a Vagrant virtual machine that integrates
the source code with various services and example data in a controlled
environment. This enables developers and users to test
MySQL and PostgreSQL backends as well as the LDAP authentication. All you
have to do is install Vagrant and run:

```
vagrant up
```

> **Note:** The first boot of the vm takes a fairly long time because
> you'll download a plain CentOS base box and Vagrant will automatically
> provision the environment on the first go.

After you should be able to browse [localhost:8080/icingaweb2](http://localhost:8080/icingaweb2).

## <a id="vagrant-login"></a>Log into Icinga Web 2

Both LDAP and a MySQL are configured as authentication backend. Please use one of the following login credentials:

> LDAP:
>> **Username**: `jdoe`

>> **Password**: `password`

>MySQL:
>> **Username**: `icingaadmin`

>> **Password**: `icinga`



## <a id="vagrant-testing"></a>Testing the Source Code

All software required to run tests is installed in the virtual machine.
In order to run all tests you have to execute the following command:

```
vagrant ssh -c "icingacli test php unit"
```
