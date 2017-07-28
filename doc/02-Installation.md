# Installation <a id="installation"></a>

The preferred way of installing Icinga Web 2 is to use the official package repositories depending on which operating
system and distribution you are running. But it is also possible to install Icinga Web 2 directly from source.

In case you are upgrading from an older version of Icinga Web 2
please make sure to read the [upgrading](02-Installation.md#upgrading) section
thoroughly.

## Installing Requirements <a id="installing-requirements"></a>

* A web server, e.g. Apache or nginx
* PHP >= 5.3.0 w/ gettext, intl, mbstring and OpenSSL support
* Default time zone configured for PHP in the php.ini file
* LDAP PHP library when using Active Directory or LDAP for authentication
* Icinga 1.x w/ IDO; Icinga 2.x w/ IDO feature enabled
* The IDO table prefix must be icinga_ which is the default
* MySQL or PostgreSQL PHP libraries
* cURL PHP library when using the Icinga 2 API for transmitting external commands

### PageSpeed Module Incompatibility <a id="pagespeed-incompatibility"></a>

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

## Installing Icinga Web 2 from Package <a id="installing-from-package"></a>

Below is a list of official package repositories for installing Icinga Web 2 for various operating systems.

| Distribution  | Repository |
| ------------- | ---------- |
| Debian        | [Icinga Repository](http://packages.icinga.com/debian/) |
| Ubuntu        | [Icinga Repository](http://packages.icinga.com/ubuntu/) |
| RHEL/CentOS   | [Icinga Repository](http://packages.icinga.com/epel/) |
| openSUSE      | [Icinga Repository](http://packages.icinga.com/openSUSE/) |
| SLES          | [Icinga Repository](http://packages.icinga.com/SUSE/) |
| Gentoo        | [Upstream](https://packages.gentoo.org/packages/www-apps/icingaweb2) |
| FreeBSD       | [Upstream](http://portsmon.freebsd.org/portoverview.py?category=net-mgmt&portname=icingaweb2) |
| ArchLinux     | [Upstream](https://aur.archlinux.org/packages/icingaweb2) |
| Alpine Linux  | [Upstream](http://git.alpinelinux.org/cgit/aports/tree/community/icingaweb2/APKBUILD) |

Packages for distributions other than the ones listed above may also be available.
Please contact your distribution packagers.

### Setting up Package Repositories <a id="package-repositories"></a>

You need to add the Icinga repository to your package management configuration for installing Icinga Web 2.
If you've already configured your OS to use the Icinga repository for installing Icinga 2, you may skip this step.
Below is a list with **examples** for various distributions.

**Debian Jessie**:
```
wget -O - http://packages.icinga.com/icinga.key | apt-key add -
echo 'deb http://packages.icinga.com/debian icinga-jessie main' >/etc/apt/sources.list.d/icinga.list
apt-get update
```
> INFO
>
> For other Debian versions just replace jessie with your distribution's code name.

**Ubuntu Xenial**:
```
wget -O - http://packages.icinga.com/icinga.key | apt-key add -
add-apt-repository 'deb http://packages.icinga.com/ubuntu icinga-xenial main'
apt-get update
```
> INFO
>
> For other Ubuntu versions just replace xenial with your distribution's code name.

**RHEL and CentOS**:
```
rpm --import http://packages.icinga.com/icinga.key
curl -o /etc/yum.repos.d/ICINGA-release.repo http://packages.icinga.com/epel/ICINGA-release.repo
yum makecache
```

**Fedora**:
```
rpm --import http://packages.icinga.com/icinga.key
curl -o /etc/yum.repos.d/ICINGA-release.repo http://packages.icinga.com/fedora/ICINGA-release.repo
yum makecache
```

**SLES 11**:
```
zypper ar http://packages.icinga.com/SUSE/ICINGA-release-11.repo
zypper ref
```

**SLES 12**:
```
zypper ar http://packages.icinga.com/SUSE/ICINGA-release.repo
zypper ref
```

**openSUSE**:
```
zypper ar http://packages.icinga.com/openSUSE/ICINGA-release.repo
zypper ref
```

**Alpine Linux**:
```
echo "http://dl-cdn.alpinelinux.org/alpine/edge/community" >> /etc/apk/repos
apk update
```
> INFO
>
> Latest version of Icinga Web 2 is in the edge repository, which is the -dev branch.

#### RHEL/CentOS Notes <a id="package-repositories-rhel-notes"></a>

The packages for RHEL/CentOS depend on other packages which are distributed as part of the
[EPEL repository](http://fedoraproject.org/wiki/EPEL). Please make sure to enable this repository by following
[these instructions](http://fedoraproject.org/wiki/EPEL#How_can_I_use_these_extra_packages.3F).

> Please note that installing Icinga Web 2 on **RHEL/CentOS 5** is not supported due to EOL versions of PHP and PostgreSQL.


#### Alpine Linux Notes <a id="package-repositories-alpine-notes"></a>

The example provided suppose that you are running Alpine edge, which is the -dev branch and is a rolling release.
If you are using a stable version, in order to use the latest Icinga Web 2 version you should "pin" the edge repository.
In order to correctly manage your repository, please follow
[these instructions](https://wiki.alpinelinux.org/wiki/Alpine_Linux_package_management).

### Installing Icinga Web 2 <a id="installing-from-package-example"></a>

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

**Alpine Linux**:
```
apk add icingaweb2
```
For Alpine Linux please read the [package repositories notes](#package-repositories-alpine-notes).

### Preparing Web Setup <a id="preparing-web-setup-from-package"></a>

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

## Installing Icinga Web 2 from Source <a id="installing-from-source"></a>

Although the preferred way of installing Icinga Web 2 is to use packages, it is also possible to install Icinga Web 2
directly from source.

### Getting the Source <a id="getting-the-source"></a>

First of all, you need to download the sources. Icinga Web 2 is available through a Git repository. You can clone this
repository either via git or http protocol using the following URLs:

  * git://git.icinga.com/icingaweb2.git
  * http://git.icinga.com/icingaweb2.git

There is also a browsable version available at
[git.icinga.com](https://git.icinga.com/?p=icingaweb2.git;a=summary "Icinga Web 2 Git Repository").
This version also offers snapshots for easy download which you can use if you do not have git present on your system.

```
git clone git://git.icinga.com/icingaweb2.git
```

### Installing Requirements from Source <a id="installing-from-source-requirements"></a>

You will need to install certain dependencies depending on your setup listed [here](02-Installation.md#installing-requirements).

The following example installs Apache2 as web server, MySQL as RDBMS and uses the PHP adapter for MySQL.
Adopt the package requirements to your needs (e.g. adding ldap for authentication) and distribution.

Example for RHEL/CentOS/Fedora:

```
yum install httpd mysql-server
yum install php php-gd php-intl php-ZendFramework php-ZendFramework-Db-Adapter-Pdo-Mysql
```

The setup wizard will check the pre-requisites later on.


### Installing Icinga Web 2 <a id="installing-from-source-example"></a>

Choose a target directory and move Icinga Web 2 there.

```
mv icingaweb2 /usr/share/icingaweb2
```

### Configuring the Web Server <a id="configuring-web-server"></a>

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

Example for Apache on Alpine Linux:
```
icingacli setup config webserver apache --document-root /usr/share/webapps/icingaweb2/public > /etc/apache2/conf.d/icingaweb2.conf
```
### Preparing Icinga Web 2 Setup <a id="preparing-web-setup-from-source"></a>

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

**Alpine Linux**:
```
gpasswd -a apache icingaweb2
rc-service apache2 restart
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

### Icinga Web 2 Setup Wizard <a id="web-setup-from-source-wizard"></a>

Finally visit Icinga Web 2 in your browser to access the setup wizard and complete the installation:
`/icingaweb2/setup`.

Paste the previously generated token and follow the steps on-screen. Then you are done here.


### Icinga Web 2 Manual Setup <a id="web-setup-manual-from-source"></a>

If you have chosen not to run the setup wizard, you will need further knowledge
about

* manual creation of the Icinga Web 2 database `icingaweb2` including a default user (optional as authentication and session backend)
* additional configuration for the application
* additional configuration for the monitoring module (e.g. the IDO database and external command pipe from Icinga 2)

This comes in handy if you are planning to deploy Icinga Web 2 automatically using
Puppet, Ansible, Chef, etc.

> **Warning**
>
> Read the documentation on the respective linked configuration sections before
> deploying the configuration manually.
>
> If you are unsure about certain settings, use the [setup wizard](02-Installation.md#web-setup-wizard-from-source) once
> and then collect the generated configuration as well as sql dumps.

#### Icinga Web 2 Manual Database Setup <a id="web-setup-manual-from-source-database"></a>

Create the database and add a new user as shown below for MySQL/MariaDB:

```
sudo mysql -p

CREATE DATABASE icingaweb2;
GRANT SELECT, INSERT, UPDATE, DELETE, DROP, CREATE VIEW, INDEX, EXECUTE ON icingaweb2.* TO 'icingaweb2'@'localhost' IDENTIFIED BY 'icingaweb2';
quit

mysql -p icingaweb2 < /usr/share/doc/icingaweb2/schema/mysql.schema.sql
```


Then generate a new password hash as described in the [authentication docs](05-Authentication.md#authentication-configuration-db-setup)
and use it to insert a new user called `icingaadmin` into the database.

```
mysql -p icingaweb2

INSERT INTO icingaweb_user (name, active, password_hash) VALUES ('icingaadmin', 1, '$1$EzxLOFDr$giVx3bGhVm4lDUAw6srGX1');
quit
```

#### Icinga Web 2 Manual Configuration <a id="web-setup-manual-from-source-config"></a>


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

#### Icinga Web 2 Manual Configuration Monitoring Module <a id="web-setup-manual-from-source-config-monitoring-module"></a>


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

**commandtransports.ini** defining the Icinga 2 API command transport.

```
vim /etc/icingaweb2/modules/monitoring/commandtransports.ini

[icinga2]
transport = "api"
host = "localhost"
port = "5665"
username = "api"
password = "api"
```

#### Icinga Web 2 Manual Setup Login <a id="web-setup-manual-from-source-login"></a>

Finally visit Icinga Web 2 in your browser to login as `icingaadmin` user: `/icingaweb2`.

## Automating the Installation of Icinga Web 2

If you are automating the installation of Icinga Web 2, you may want to skip the wizard and do things yourself.
These are the steps you'd need to take assuming you are using MySQL/MariaDB. If you are using PostgreSQL please adapt
accordingly. Note you need to have successfully completed the Icinga 2 installation, installed the Icinga Web 2 packages
and all the other steps described above first.

1. Install PHP dependencies: `php`, `php-intl`, `php-imagick`, `php-gd`, `php-mysql`, `php-curl`, `php-mbstring` used
by Icinga Web 2.
2. Set a timezone in `php.ini` configuration file.
3. Create a database for Icinga Web 2, i.e. `icingaweb2`.
4. Import the database schema: `mysql -D icingaweb2 < /usr/share/icingaweb2/etc/schema/mysql.schema.sql`.
5. Insert administrator user in the `icingaweb2` database:
`INSERT INTO icingaweb_user (name, active, password_hash) VALUES ('admin', 1, '<hash>')`, where `<hash>` is the output
of `openssl passwd -1 <password>`.
5. Make sure the `ido-mysql` and `api` features are enabled in Icinga 2: `icinga2 feature enable ido-mysql` and 
`icinga2 feature enable api`.
6. Generate Apache/nginx config. This command will print an apacahe config for you on stdout:
`icingacli setup config webserver apache`. Similarly for nginx. You need to place that configuration in the right place,
for example `/etc/apache2/sites-enabled/icingaweb2.conf`.
7. Add `www-data` user to `icingaweb2` group if not done already (`usermod -a -G icingaweb2 www-data`).
8. Create the Icinga Web 2 configuration in `/etc/icingaweb2`. The directory can be easily created with:
`icingacli setup config webserver`. This command ensures that the directory has the appropriate ownership and
permissions. If you want to create the directory manually, make sure to chown the group to `icingaweb2` and set the
access mode to `2770`.

The structure of the configurations looks like the following:

```
/etc/icingaweb2/
/etc/icingaweb2/authentication.ini
/etc/icingaweb2/modules
/etc/icingaweb2/modules/monitoring
/etc/icingaweb2/modules/monitoring/config.ini
/etc/icingaweb2/modules/monitoring/instances.ini
/etc/icingaweb2/modules/monitoring/backends.ini
/etc/icingaweb2/roles.ini
/etc/icingaweb2/config.ini
/etc/icingaweb2/enabledModules
/etc/icingaweb2/enabledModules/monitoring
/etc/icingaweb2/enabledModules/doc
/etc/icingaweb2/resources.ini
```

Have a look [here](#web-setup-manual-from-source-config) for the contents of the files.

## Upgrading Icinga Web 2 <a id="upgrading"></a>

### Upgrading to Icinga Web 2 2.4.x <a id="upgrading-to-2.4.x"></a>

* Icinga Web 2 version 2.4.x does not introduce any backward incompatible change.

### Upgrading to Icinga Web 2 2.3.x <a id="upgrading-to-2.3.x"></a>

* Icinga Web 2 version 2.3.x does not introduce any backward incompatible change.

### Upgrading to Icinga Web 2 2.2.0 <a id="upgrading-to-2.2.0"></a>

* The menu entry `Authorization` beneath `Config` has been renamed to `Authentication`. The role, user backend and user
  group backend configuration which was previously found beneath `Authentication` has been moved to `Application`.
  
### Upgrading to Icinga Web 2 2.1.x <a id="upgrading-to-2.1.x"></a>

* Since Icinga Web 2 version 2.1.3 LDAP user group backends respect the configuration option `group_filter`.
  Users who changed the configuration manually and used the option `filter` instead
  have to change it back to `group_filter`.

### Upgrading to Icinga Web 2 2.0.0 <a id="upgrading-to-2.0.0"></a>

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

### Upgrading to Icinga Web 2 Release Candidate 1 <a id="upgrading-to-rc1"></a>

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

### Upgrading to Icinga Web 2 Beta 3 <a id="upgrading-to-beta3"></a>

Because Icinga Web 2 Beta 3 does not introduce any backward incompatible change you don't have to change your
configuration files after upgrading to Icinga Web 2 Beta 3.

### Upgrading to Icinga Web 2 Beta 2 <a id="upgrading-to-beta2"></a>

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
