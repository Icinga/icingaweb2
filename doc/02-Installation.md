# <a id="installation"></a> Installation

The preferred way of installing Icinga Web 2 is to use the official package repositories depending on which operating
system and distribution you are running. But it is also possible to install Icinga Web 2 directly from source.

In case you are upgrading from an older version of Icinga Web 2
please make sure to read the [upgrading](02-Installation.md#upgrading) section
thoroughly.

## <a id="installing-requirements"></a> Installing Requirements

* A web server, e.g. Apache or nginx
* PHP >= 5.3.0 w/ gettext, intl and OpenSSL support
* Default time zone configured for PHP in the php.ini file
* LDAP PHP library when using Active Directory or LDAP for authentication
* Icinga 1.x w/ IDO; Icinga 2.x w/ IDO feature enabled
* The IDO table prefix must be icinga_ which is the default
* MySQL or PostgreSQL PHP libraries
* cURL PHP library when using the Icinga 2 API for transmitting external commands

### <a id="pagespeed-incompatibility"></a> PageSpeed Module Incompatibility

It seems that Web 2 is not compatible with the PageSpeed module. Please disable the PageSpeed module using one of the
following methods.

**Apache**:
```
ModPagespeedDisallow "*/icingaweb2/*"
```

**Nginx**:
```
pagespeed Disallow "*/icingaweb2/*";
```

## <a id="installing-from-package"></a> Installing Icinga Web 2 from Package

Below is a list of official package repositories for installing Icinga Web 2 for various operating systems.

| Distribution  | Repository |
| ------------- | ---------- |
| Debian        | [Icinga Repository](http://packages.icinga.org/debian/) |
| Ubuntu        | [Icinga Repository](http://packages.icinga.org/ubuntu/) |
| RHEL/CentOS   | [Icinga Repository](http://packages.icinga.org/epel/) |
| openSUSE      | [Icinga Repository](http://packages.icinga.org/openSUSE/) |
| SLES          | [Icinga Repository](http://packages.icinga.org/SUSE/) |
| Gentoo        | [Upstream](https://packages.gentoo.org/packages/www-apps/icingaweb2) |
| FreeBSD       | [Upstream](http://portsmon.freebsd.org/portoverview.py?category=net-mgmt&portname=icingaweb2) |
| ArchLinux     | [Upstream](https://aur.archlinux.org/packages/icingaweb2) |

Packages for distributions other than the ones listed above may also be available.
Please contact your distribution packagers.

### <a id="package-repositories"></a> Setting up Package Repositories

You need to add the Icinga repository to your package management configuration for installing Icinga Web 2.
If you've already configured your OS to use the Icinga repository for installing Icinga 2, you may skip this step.
Below is a list with **examples** for various distributions.

**Debian Jessie**:
```
wget -O - http://packages.icinga.org/icinga.key | apt-key add -
echo 'deb http://packages.icinga.org/debian icinga-jessie main' >/etc/apt/sources.list.d/icinga.list
apt-get update
```

> INFO
>
> For other Debian versions just replace jessie with your distribution's code name.

**Ubuntu Xenial**:
```
wget -O - http://packages.icinga.org/icinga.key | apt-key add -
add-apt-repository 'deb http://packages.icinga.org/ubuntu icinga-xenial main'
apt-get update
```
> INFO
>
> For other Ubuntu versions just replace xenial with your distribution's code name.

**RHEL and CentOS**:
```
rpm --import http://packages.icinga.org/icinga.key
curl -o /etc/yum.repos.d/ICINGA-release.repo http://packages.icinga.org/epel/ICINGA-release.repo
yum makecache
```

**Fedora**:
```
rpm --import http://packages.icinga.org/icinga.key
curl -o /etc/yum.repos.d/ICINGA-release.repo http://packages.icinga.org/fedora/ICINGA-release.repo
yum makecache
```

**SLES 11**:
```
zypper ar http://packages.icinga.org/SUSE/ICINGA-release-11.repo
zypper ref
```

**SLES 12**:
```
zypper ar http://packages.icinga.org/SUSE/ICINGA-release.repo
zypper ref
```

**openSUSE**:
```
zypper ar http://packages.icinga.org/openSUSE/ICINGA-release.repo
zypper ref
```

#### <a id="package-repositories-rhel-notes"></a> RHEL/CentOS Notes

The packages for RHEL/CentOS depend on other packages which are distributed as part of the
[EPEL repository](http://fedoraproject.org/wiki/EPEL). Please make sure to enable this repository by following
[these instructions](http://fedoraproject.org/wiki/EPEL#How_can_I_use_these_extra_packages.3F).

> Please note that installing Icinga Web 2 on **RHEL/CentOS 5** is not supported due to EOL versions of PHP and PostgreSQL.

### <a id="installing-from-package-example"></a> Installing Icinga Web 2

You can install Icinga Web 2 by using your distribution's package manager to install the `icingaweb2` package.
Below is a list with examples for various distributions. The additional package `icingacli` is necessary on RPM based systems for being able to follow further steps in this guide. In DEB based systems, the icingacli binary is included in the icingaweb2 package.

**Debian and Ubuntu**:
```
apt-get install icingaweb2
```

**RHEL, CentOS and Fedora**:
```
yum install icingaweb2 icingacli
```
For RHEL/CentOS please read the [package repositories notes](#package-repositories-rhel-notes).

**SLES and openSUSE**:
```
zypper install icingaweb2 icingacli
```

### <a id="preparing-web-setup-from-package"></a> Preparing Web Setup

You can set up Icinga Web 2 quickly and easily with the Icinga Web 2 setup wizard which is available the first time
you visit Icinga Web 2 in your browser. When using the web setup you are required to authenticate using a token.
In order to generate a token use the `icingacli`:
```
icingacli setup token create
```

In case you do not remember the token you can show it using the `icingacli`:
```
icingacli setup token show
```

Finally visit Icinga Web 2 in your browser to access the setup wizard and complete the installation:
`/icingaweb2/setup`.

## <a id="installing-from-source"></a> Installing Icinga Web 2 from Source

Although the preferred way of installing Icinga Web 2 is to use packages, it is also possible to install Icinga Web 2
directly from source.

### <a id="getting-the-source"></a> Getting the Source

First of all, you need to download the sources. Icinga Web 2 is available through a Git repository. You can clone this
repository either via git or http protocol using the following URLs:

  * git://git.icinga.org/icingaweb2.git
  * http://git.icinga.org/icingaweb2.git

There is also a browsable version available at
[git.icinga.org](https://git.icinga.org/?p=icingaweb2.git;a=summary "Icinga Web 2 Git Repository").
This version also offers snapshots for easy download which you can use if you do not have git present on your system.

```
git clone git://git.icinga.org/icingaweb2.git
```

### <a id="installing-from-source-requirements"></a> Installing Requirements from Source

You will need to install certain dependencies depending on your setup listed [here](02-Installation.md#installing-requirements).

The following example installs Apache2 as web server, MySQL as RDBMS and uses the PHP adapter for MySQL.
Adopt the package requirements to your needs (e.g. adding ldap for authentication) and distribution.

Example for RHEL/CentOS/Fedora:

```
yum install httpd mysql-server
yum install php php-gd php-intl php-ZendFramework php-ZendFramework-Db-Adapter-Pdo-Mysql
```

The setup wizard will check the pre-requisites later on.


### <a id="installing-from-source-example"></a> Installing Icinga Web 2

Choose a target directory and move Icinga Web 2 there.

```
mv icingaweb2 /usr/share/icingaweb2
```

### <a id="configuring-web-server"></a> Configuring the Web Server

Use `icingacli` to generate web server configuration for either Apache or nginx.

**Apache**:
```
./bin/icingacli setup config webserver apache --document-root /usr/share/icingaweb2/public
```

**nginx**:
```
./bin/icingacli setup config webserver nginx --document-root /usr/share/icingaweb2/public
```

Save the output as new file in your webserver's configuration directory.

Example for Apache on RHEL or CentOS:
```
./bin/icingacli setup config webserver apache --document-root /usr/share/icingaweb2/public > /etc/httpd/conf.d/icingaweb2.conf
```

Example for Apache on SUSE:
```
./bin/icingacli setup config webserver apache --document-root /usr/share/icingaweb2/public > /etc/apache2/conf.d/icingaweb2.conf
```

Example for Apache on Debian Jessie:
```
./bin/icingacli setup config webserver apache --document-root /usr/share/icingaweb2/public > /etc/apache2/conf-available/icingaweb2.conf
a2enconf icingaweb2
```

### <a id="preparing-web-setup-from-source"></a> Preparing Icinga Web 2 Setup

You can set up Icinga Web 2 quickly and easily with the Icinga Web 2 setup wizard which is available the first time
you visit Icinga Web 2 in your browser. Please follow the steps listed below for preparing the web setup.

Because both web and CLI must have access to configuration and logs, permissions will be managed using a special
system group. The web server user and CLI user have to be added to this system group.

Add the system group `icingaweb2` in the first place.

**Fedora, RHEL, CentOS, SLES and OpenSUSE**:
```
groupadd -r icingaweb2
```

**Debian and Ubuntu**:
```
addgroup --system icingaweb2
```

Add your web server's user to the system group `icingaweb2`
and restart the web server:

**Fedora, RHEL and CentOS**:
```
usermod -a -G icingaweb2 apache
service httpd restart
```

**SLES and OpenSUSE**:
```
usermod -A icingaweb2 wwwrun
service apache2 restart
```

**Debian and Ubuntu**:
```
usermod -a -G icingaweb2 www-data
service apache2 restart
```


Use `icingacli` to create the configuration directory which defaults to **/etc/icingaweb2**:
```
./bin/icingacli setup config directory
```


When using the web setup you are required to authenticate using a token. In order to generate a token use the
`icingacli`:
```
./bin/icingacli setup token create
```

In case you do not remember the token you can show it using the `icingacli`:
```
./bin/icingacli setup token show
```

### <a id="web-setup-from-source-wizard"></a> Icinga Web 2 Setup Wizard

Finally visit Icinga Web 2 in your browser to access the setup wizard and complete the installation:
`/icingaweb2/setup`.

Paste the previously generated token and follow the steps on-screen. Then you are done here.


### <a id="web-setup-manual-from-source"></a> Icinga Web 2 Manual Setup

If you have chosen not to run the setup wizard, you will need further knowledge
about

* manual creation of the Icinga Web 2 database `icingaweb2` including a default user (optional as authentication and session backend)
* additional configuration for the application
* additional configuration for the monitoring module (e.g. the IDO database and external command pipe from Icinga 2)

This comes in handy if you are planning to deploy Icinga Web 2 automatically using
Puppet, Ansible, Chef, etc. modules.

> **Warning**
>
> Read the documentation on the respective linked configuration sections before
> deploying the configuration manually.
>
> If you are unsure about certain settings, use the [setup wizard](02-Installation.md#web-setup-wizard-from-source) once
> and then collect the generated configuration as well as sql dumps.

#### <a id="web-setup-manual-from-source-database"></a> Icinga Web 2 Manual Database Setup

Create the database and add a new user as shown below for MySQL:

```
sudo mysql -p

CREATE DATABASE icingaweb2;
GRANT SELECT, INSERT, UPDATE, DELETE, DROP, CREATE VIEW, INDEX, EXECUTE ON icingaweb2.* TO 'icingaweb2'@'localhost' IDENTIFIED BY 'icingaweb2';
quit

mysql -p icingaweb2 < /usr/share/icingaweb2/etc/schema/mysql.schema.sql
```


Then generate a new password hash as described in the [authentication docs](05-Authentication.md#authentication-configuration-db-setup)
and use it to insert a new user called `icingaadmin` into the database.

```
mysql -p icingaweb2

INSERT INTO icingaweb_user (name, active, password_hash) VALUES ('icingaadmin', 1, '$1$EzxLOFDr$giVx3bGhVm4lDUAw6srGX1');
quit
```

#### <a id="web-setup-manual-from-source-config"></a> Icinga Web 2 Manual Configuration


[resources.ini](04-Resources.md#resources) providing the details for the Icinga Web 2 and
Icinga 2 IDO database configuration. Example for MySQL:

```
vim /etc/icingaweb2/resources.ini

[icingaweb2]
type                = "db"
db                  = "mysql"
host                = "localhost"
port                = "3306"
dbname              = "icingaweb2"
username            = "icingaweb2"
password            = "icingaweb2"


[icinga2]
type                = "db"
db                  = "mysql"
host                = "localhost"
port                = "3306"
dbname              = "icinga"
username            = "icinga"
password            = "icinga"
```

[config.ini](03-Configuration.md#configuration) defining general application settings.

```
vim /etc/icingaweb2/config.ini

[logging]
log                 = "syslog"
level               = "ERROR"
application         = "icingaweb2"


[preferences]
type                = "db"
resource            = "icingaweb2"
```

[authentication.ini](05-Authentication.md#authentication) for e.g. using the previously created database.

```
vim /etc/icingaweb2/authentication.ini

[icingaweb2]
backend             = "db"
resource            = "icingaweb2"
```


[roles.ini](06-Security.md#security) granting the previously added `icingaadmin` user all permissions.

```
vim /etc/icingaweb2/roles.ini

[admins]
users               = "icingaadmin"
permissions         = "*"
```

#### <a id="web-setup-manual-from-source-config-monitoring-module"></a> Icinga Web 2 Manual Configuration Monitoring Module


**config.ini** defining additional security settings.

```
vim /etc/icingaweb2/modules/monitoring/config.ini

[security]
protected_customvars = "*pw*,*pass*,community"
```

**backends.ini** referencing the Icinga 2 DB IDO resource.

```
vim /etc/icingaweb2/modules/monitoring/backends.ini

[icinga2]
type                = "ido"
resource            = "icinga2"
```

**commandtransports.ini** defining the Icinga command pipe.

```
vim /etc/icingaweb2/modules/monitoring/commandtransports.ini

[icinga2]
transport           = "local"
path                = "/var/run/icinga2/cmd/icinga2.cmd"
```

#### <a id="web-setup-manual-from-source-login"></a> Icinga Web 2 Manual Setup Login

Finally visit Icinga Web 2 in your browser to login as `icingaadmin` user: `/icingaweb2`.


## <a id="upgrading"></a> Upgrading Icinga Web 2

### <a id="upgrading-to-2.4.0"></a> Upgrading to Icinga Web 2 2.4.0

* Icinga Web 2 version 2.4.0 does not introduce any backward incompatible change.

### <a id="upgrading-to-2.3.x"></a> Upgrading to Icinga Web 2 2.3.x

* Icinga Web 2 version 2.3.x does not introduce any backward incompatible change.

### <a id="upgrading-to-2.2.0"></a> Upgrading to Icinga Web 2 2.2.0

* The menu entry `Authorization` beneath `Config` has been renamed to `Authentication`. The role, user backend and user
  group backend configuration which was previously found beneath `Authentication` has been moved to `Application`.
  
### <a id="upgrading-to-2.1.x"></a> Upgrading to Icinga Web 2 2.1.x

* Since Icinga Web 2 version 2.1.3 LDAP user group backends respect the configuration option `group_filter`.
  Users who changed the configuration manually and used the option `filter` instead
  have to change it back to `group_filter`.

### <a id="upgrading-to-2.0.0"></a> Upgrading to Icinga Web 2 2.0.0

* Icinga Web 2 installations from package on RHEL/CentOS 7 now depend on `php-ZendFramework` which is available through
  the [EPEL repository](http://fedoraproject.org/wiki/EPEL). Before, Zend was installed as Icinga Web 2 vendor library
  through the package `icingaweb2-vendor-zend`. After upgrading, please make sure to remove the package
  `icingaweb2-vendor-zend`.

* Icinga Web 2 version 2.0.0 requires permissions for accessing modules. Those permissions are automatically generated
  for each installed module in the format `module/<moduleName>`. Administrators have to grant the module permissions to
  users and/or user groups in the roles configuration for permitting access to specific modules.
  In addition, restrictions provided by modules are now configurable for each installed module too. Before,
  a module had to be enabled before having the possibility to configure restrictions.

* The **instances.ini** configuration file provided by the monitoring module
  has been renamed to **commandtransports.ini**. The content and location of
  the file remains unchanged.

* The location of a user's preferences has been changed from
  **&lt;config-dir&gt;/preferences/&lt;username&gt;.ini** to
  **&lt;config-dir&gt;/preferences/&lt;username&gt;/config.ini**.
  The content of the file remains unchanged.

### <a id="upgrading-to-rc1"></a> Upgrading to Icinga Web 2 Release Candidate 1

The first release candidate of Icinga Web 2 introduces the following non-backward compatible changes:

* The database schema has been adjusted and the tables `icingaweb_group` and
  `icingaweb_group_membership` were altered to ensure referential integrity.
  Please use the upgrade script located in **etc/schema/** to update your
  database schema

* Users who are using PostgreSQL < v9.1 are required to upgrade their
  environment to v9.1+ as this is the new minimum required version
  for utilizing PostgreSQL as database backend

* The restrictions `monitoring/hosts/filter` and `monitoring/services/filter`
  provided by the monitoring module were merged together. The new
  restriction is called `monitoring/filter/objects` and supports only a
  predefined subset of filter columns. Please see the module's security
  related documentation for more details.

### <a id="upgrading-to-beta3"></a> Upgrading to Icinga Web 2 Beta 3

Because Icinga Web 2 Beta 3 does not introduce any backward incompatible change you don't have to change your
configuration files after upgrading to Icinga Web 2 Beta 3.

### <a id="upgrading-to-beta2"></a> Upgrading to Icinga Web 2 Beta 2

Icinga Web 2 Beta 2 introduces access control based on roles for secured actions. If you've already set up Icinga Web 2,
you are required to create the file **roles.ini** beneath Icinga Web 2's configuration directory with the following
content:
```
[administrators]
users = "your_user_name, another_user_name"
permissions = "*"
```

After please log out from Icinga Web 2 and log in again for having all permissions granted.

If you delegated authentication to your web server using the `autologin` backend, you have to switch to the `external`
authentication backend to be able to log in again. The new name better reflects 
what's going on. A similar change
affects environments that opted for not storing preferences, your new backend is `none`.
