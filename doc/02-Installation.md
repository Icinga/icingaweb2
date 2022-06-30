<!-- {% if index %} -->
# Installation <a id="installation"></a>

The preferred way of installing Icinga Web 2 is to use the official package repositories depending on which operating
system and distribution you are running.

Please follow the steps listed for your operating system. Packages for distributions other than the ones
listed here may also be available. Please refer to [icinga.com/get-started/download](https://icinga.com/get-started/download/#community)
for a full list of available community repositories.

## Browser Support

Icinga Web 2 and modules made by Icinga don't require a particular browser or set of browsers. The
vendor of the browser in question doesn't matter much. However, the features a browser supports do.

This generally applies to CSS and Javascript features. Since there a plethora of features in each
category which Icinga Web 2 and modules may require, we will only mention the most prominent feature
or sub-category here:

* For CSS this is [the flexible box layout module](https://caniuse.com/flexbox)
* For Javascript it is [the ECMAScript 2015 specification](https://caniuse.com/es6)

If your desired browser and its version is showing up in green when visiting the respective link,
it's probably okay to use it for Icinga Web 2.

## Upgrade <a id="upgrade"></a>

In case you are upgrading from an older version of Icinga Web 2
please make sure to read the [upgrading](80-Upgrading.md#upgrading) section
thoroughly.
<!-- {% elif not from_source %} -->

## Installation Requirements <a id="installation-requirements"></a>

* [Icinga 2](https://icinga.com/docs/icinga-2) and [Icinga DB](https://icinga.com/docs/icinga-db) to
  monitor your infrastructure
* A web server, e.g. Apache or Nginx
* PHP version ≥ 7.2

### Optional Requirements

* The [pdfexport](https://github.com/Icinga/icingaweb2-module-pdfexport) module (≥0.10) is required for the
  export to PDF
* LDAP PHP library when using Active Directory or LDAP for authentication

## Add Icinga Package Repository <a id="add-icinga-package-repository"></a>

You need to add the Icinga repository to your package management configuration for installing Icinga Web 2.
If you've already configured your OS to use the Icinga repository for installing Icinga 2, you may skip this step.

<!-- {% if debian %} -->
### Debian Repository <a id="ubuntu-repository"></a>

```bash
apt-get update
apt-get -y install apt-transport-https wget gnupg

wget -O - https://packages.icinga.com/icinga.key | apt-key add -

DIST=$(awk -F"[)(]+" '/VERSION=/ {print $2}' /etc/os-release); \
 echo "deb https://packages.icinga.com/debian icinga-${DIST} main" > \
 /etc/apt/sources.list.d/${DIST}-icinga.list
 echo "deb-src https://packages.icinga.com/debian icinga-${DIST} main" >> \
 /etc/apt/sources.list.d/${DIST}-icinga.list

apt-get update
```
<!-- {% endif %} -->

<!-- {% if ubuntu %} -->
### Ubuntu Repository <a id="ubuntu-repository"></a>

```bash
apt-get update
apt-get -y install apt-transport-https wget gnupg

wget -O - https://packages.icinga.com/icinga.key | apt-key add -

. /etc/os-release; if [ ! -z ${UBUNTU_CODENAME+x} ]; then DIST="${UBUNTU_CODENAME}"; else DIST="$(lsb_release -c| awk '{print $2}')"; fi; \
 echo "deb https://packages.icinga.com/ubuntu icinga-${DIST} main" > \
 /etc/apt/sources.list.d/${DIST}-icinga.list
 echo "deb-src https://packages.icinga.com/ubuntu icinga-${DIST} main" >> \
 /etc/apt/sources.list.d/${DIST}-icinga.list

apt-get update
```
<!-- {% endif %} -->

<!-- {% if centos %} -->
### CentOS Repository <a id="centos-repository"></a>

```bash
rpm --import https://packages.icinga.com/icinga.key
wget https://packages.icinga.com/centos/ICINGA-release.repo -O /etc/yum.repos.d/ICINGA-release.repo
```

The packages for CentOS depend on other packages which are distributed
as part of the [EPEL repository](https://fedoraproject.org/wiki/EPEL).

CentOS 7:

```bash
yum install epel-release
```

Since Icinga Web v2.5 we also require a **newer PHP version** than what is available
in RedHat itself. You need to enable the SCL repository, so that the dependencies
can pull in the newer PHP.

```bash
yum install centos-release-scl
```
<!-- {% endif %} -->

<!-- {% if rhel %} -->
### RHEL Repository <a id="rhel-repository"></a>

!!! info

    A paid repository subscription is required for RHEL repositories. Get more information on
    [icinga.com/subscription](https://icinga.com/subscription)

    Don't forget to fill in the username and password section with your credentials in the local .repo file.

```bash
rpm --import https://packages.icinga.com/icinga.key
wget https://packages.icinga.com/subscription/rhel/ICINGA-release.repo -O /etc/yum.repos.d/ICINGA-release.repo
```

If you are using RHEL you need to additionally enable the `optional` and `codeready-builder`
repository before installing the [EPEL rpm package](https://fedoraproject.org/wiki/EPEL#How_can_I_use_these_extra_packages.3F).

#### RHEL 8

```bash
ARCH=$( /bin/arch )

subscription-manager repos --enable rhel-8-server-optional-rpms
subscription-manager repos --enable "codeready-builder-for-rhel-8-${ARCH}-rpms"

dnf install https://dl.fedoraproject.org/pub/epel/epel-release-latest-8.noarch.rpm
```

#### RHEL 7
Since Icinga Web v2.5 we also require a **newer PHP version** than what is available
in RedHat itself. You need to enable the SCL repository, so that the dependencies
can pull in the newer PHP.

```bash
subscription-manager repos --enable rhel-7-server-optional-rpms
subscription-manager repos --enable rhel-server-rhscl-7-rpms

yum install https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm
```
<!-- {% endif %} -->

<!-- {% if sles %} -->
### SLES Repository <a id="rhel-repository"></a>

!!! info

    A paid repository subscription is required for RHEL repositories. Get more information on
    [icinga.com/subscription](https://icinga.com/subscription)

    Don't forget to fill in the username and password section with your credentials in the local .repo file.

```bash
rpm --import https://packages.icinga.com/icinga.key

zypper ar https://packages.icinga.com/subscription/sles/ICINGA-release.repo
zypper ref
```

You need to additionally enable a couple of SLES repositories to fulfill dependencies:

```bash
source /etc/os-release

SUSEConnect -p sle-module-desktop-applications/$VERSION_ID/x86_64
SUSEConnect -p sle-module-development-tools/$VERSION_ID/x86_64
SUSEConnect -p sle-module-web-scripting/$VERSION_ID/x86_64
SUSEConnect -p PackageHub/$VERSION_ID/x86_64
```
<!-- {% endif %} -->

<!-- {% if amazon_linux %} -->
### Amazon Linux 2 Repository <a id="amazon-linux-2-repository"></a>

!!! info

    A paid repository subscription is required for Amazon Linux repositories. Get more information on
    [icinga.com/subscription](https://icinga.com/subscription)

    Don't forget to fill in the username and password section with your credentials in the local .repo file.

```bash
rpm --import https://packages.icinga.com/icinga.key
wget https://packages.icinga.com/subscription/amazon/ICINGA-release.repo -O /etc/yum.repos.d/ICINGA-release.repo
```

You need to install and enable the `amazon-linux-extras` repository to meet the requirements of
Icinga Web 2 on Amazon Linux 2:

```bash
yum install -y amazon-linux-extras

amazon-linux-extras enable php8.0
```
<!-- {% endif %} -->

## Install Icinga Web 2 <a id="install-icingaweb2"></a>

You can install Icinga Web 2 by using your distribution's package manager to install the `icingaweb2` package.
The additional package `icingacli` is necessary to follow further steps in this guide.

<!-- {% if debian %} -->
<!-- {% if not icingaDocs %} -->
#### Debian
<!-- {% endif %} -->
```bash
apt-get install icingaweb2 icingacli
```
<!-- {% endif %} -->

<!-- {% if ubuntu %} -->
<!-- {% if not icingaDocs %} -->
#### Ubuntu
<!-- {% endif %} -->
```bash
apt-get install icingaweb2 libapache2-mod-php icingacli
```

The additional package `libapache2-mod-php` is necessary on Ubuntu to automatically
install a web server and PHP and make Icinga Web 2 work out-of-the-box.
<!-- {% endif %} -->

<!-- {% if centos or rhel or amazon_linux %} -->
!!! tip

    If you have [SELinux](90-SELinux.md) enabled, the package `icingaweb2-selinux` is also required.
<!-- {% endif %} -->

<!-- {% if centos %} -->
<!-- {% if not icingaDocs %} -->
#### CentOS
<!-- {% endif %} -->
```
dnf install icingaweb2 icingacli
```
<!-- {% endif %} -->

<!-- {% if rhel %} -->
<!-- {% if not icingaDocs %} -->
#### RHEL
<!-- {% endif %} -->
#### RHEL 8
```bash
dnf install icingaweb2 icingacli
```

#### RHEL 7
```bash
yum install icingaweb2 icingacli
```
<!-- {% endif %} -->

<!-- {% if sles %} -->
<!-- {% if not icingaDocs %} -->
#### SLES
<!-- {% endif %} -->
```bash
zypper install icingaweb2 icingacli
```
<!-- {% endif %} -->

<!-- {% if amazon_linux %} -->
<!-- {% if not icingaDocs %} -->
#### Amazon Linux 2
<!-- {% endif %} -->
```bash
yum install icingaweb2 icingacli
```
<!-- {% endif %} -->

## Install the Web Server <a id="install-the-web-server"></a>

Ensure that you have a web server with PHP installed before proceeding,
such as Apache or Nginx with PHP version ≥ 7.2. Depending on your operating system,
you may need to install and configure the web server separately.
An Apache configuration file to serve Icinga Web is already installed.
If you want to use Nginx, you must manually create a configuration file using the following command.
Save the output as a new file in the web server configuration directory:

```bash
icingacli setup config webserver nginx --document-root /usr/share/icingaweb2/public
```

## Prepare Web Setup <a id="prepare-web-setup-from-package"></a>

You can set up Icinga Web 2 quickly and easily with the Icinga Web 2 setup wizard which is available the first time
you visit Icinga Web 2 in your browser. When using the web setup you are required to authenticate using a token.
In order to generate a token use the `icingacli`:

```bash
icingacli setup token create
```

In case you do not remember the token you can show it using the `icingacli`:

```bash
icingacli setup token show
```

### Create Database
<!-- {% if debian or ubuntu %} -->
You need to manually create a database and a database user in MySQL or PostgreSQL prior to starting the web wizard.
This is due to local security restrictions whereas the web wizard cannot create a database/user through
a local unix domain socket.

#### Set up a MySQL database:

```bash
MariaDB [mysql]> CREATE DATABASE icingaweb2;

MariaDB [mysql]> GRANT ALL ON icingaweb2.* TO icingaweb2@localhost IDENTIFIED BY 'CHANGEME';
```

#### Set up a PostgreSQL database:

```bash
cd /tmp
sudo -u postgres psql -c "CREATE ROLE icingaweb2 WITH LOGIN PASSWORD 'CHANGEME'"
sudo -u postgres createdb -O icingaweb2 -E UTF8 icingaweb2
```
!!! note

    It is assumed here that your locale is set to utf-8, you may run into problems otherwise.

Locate your `pg_hba.conf` configuration file and add the icingaweb2 user with `md5` as authentication
method and restart the postgresql server. Common locations for `pg_hba.conf` are either
`/etc/postgresql/*/main/pg_hba.conf` or `/var/lib/pgsql/data/pg_hba.conf`.

```bash
# icingaweb2
local   icingaweb2      icingaweb2                            md5
host    icingaweb2      icingaweb2      127.0.0.1/32          md5
host    icingaweb2      icingaweb2      ::1/128               md5

# "local" is for Unix domain socket connections only
local   all         all                               ident
# IPv4 local connections:
host    all         all         127.0.0.1/32          ident
# IPv6 local connections:
host    all         all         ::1/128               ident
```
!!! note

    You may also create a separate administrative account with all privileges instead.

<!-- {% endif %} -->

### Start Web Setup <a id="start-web-setup-from-package"></a>

Finally visit Icinga Web 2 in your browser to access the setup wizard and complete the installation:
`/icingaweb2/setup`.

<!-- {% if debian or ubuntu %} -->
!!! hint

    Use the same database, user and password details created above when asked.
<!-- {% endif %} -->

The setup wizard automatically detects the required packages. In case one of them is missing,
e.g. a PHP module, please install the package, restart your webserver and reload the setup page.

<!-- {% if sles %} -->
!!! note

    If you're using php-fpm on SLES 15 SP2 onwards, `/etc/icingaweb2` may not be writable.
    That's because the default systemd unit file for php-fpm has `ProtectSystem=full`
    enabled. You want to lookup/add the systemd setting `ReadWritePaths=` in this case and
    add `/etc/icingaweb2` to it. Alternatively you can also define a different configuration
    directory using the environment variable `ICINGAWEB_CONFIGDIR`.
<!-- {% endif %} -->

<!-- {% if centos or rhel or amazon_linux %} -->
!!! note

    If you have SELinux enabled, please ensure to either have the selinux package for Icinga Web 2 installed, or disable it.
<!-- {% endif %} -->

<!-- {% else %} --><!-- {# end from_source elif #} -->
<!-- {% if not icingaDocs %} -->
## Installing Icinga Web 2 from Source <a id="installing-from-source"></a>
<!-- {% endif %} -->

Although the preferred way of installing Icinga Web 2 is to use packages, it is also possible to install Icinga Web 2
directly from source.

### Getting the Source <a id="getting-the-source"></a>

First of all, you need to download the sources.

Git clone:

```bash
cd /usr/share/
git clone https://github.com/Icinga/icingaweb2.git icingaweb2
```

Tarball download (latest [release](https://github.com/Icinga/icingaweb2/releases/latest)):

```bash
cd /usr/share
wget https://github.com/Icinga/icingaweb2/archive/v2.9.5.zip
unzip v2.9.5.zip
mv icingaweb2-2.9.5 icingaweb2
```

### Installing Requirements from Source <a id="installing-from-source-requirements"></a>

You will need to install certain dependencies depending on your setup:

* [Icinga 2](https://github.com/Icinga/icinga2) and [Icinga DB](https://github.com/Icinga/icingadb) to
  monitor your infrastructure
* A web server, e.g. Apache or Nginx
* PHP version ≥ 7.2
* [Icinga PHP Library (ipl)](https://github.com/Icinga/icinga-php-library) (≥ 0.9)
* [Icinga PHP Thirdparty](https://github.com/Icinga/icinga-php-thirdparty) (≥ 0.11)
* The following PHP modules must be installed: cURL, json, gettext, fileinfo, intl, dom, OpenSSL and xml
* The [pdfexport](https://github.com/Icinga/icingaweb2-module-pdfexport) module (≥0.10) is required for the
  export to PDF
* LDAP PHP library when using Active Directory or LDAP for authentication
* MySQL or PostgreSQL PHP libraries

The following example installs Apache2 as web server, MySQL as RDBMS and uses the PHP adapter for MySQL.
Adopt the package requirements to your needs (e.g. adding ldap for authentication) and distribution.

Example for RHEL/CentOS/Fedora:

```bash
yum install httpd mysql-server
yum install php php-gd php-intl
```

The setup wizard will check the pre-requisites later on.


### Installing Icinga Web 2 <a id="installing-from-source-example"></a>

Choose a target directory and move Icinga Web 2 there.

```bash
mv icingaweb2 /usr/share/icingaweb2
```

### Configuring the Web Server <a id="configuring-web-server"></a>

Use `icingacli` to generate web server configuration for either Apache or nginx.

**Apache**:
```bash
./bin/icingacli setup config webserver apache --document-root /usr/share/icingaweb2/public
```

**nginx**:
```bash
./bin/icingacli setup config webserver nginx --document-root /usr/share/icingaweb2/public
```

Save the output as new file in your webserver's configuration directory.

Example for Apache on RHEL or CentOS:
```bash
./bin/icingacli setup config webserver apache --document-root /usr/share/icingaweb2/public > /etc/httpd/conf.d/icingaweb2.conf
```

Example for Apache on SUSE:
```bash
./bin/icingacli setup config webserver apache --document-root /usr/share/icingaweb2/public > /etc/apache2/conf.d/icingaweb2.conf
```

Example for Apache on Debian Jessie:
```bash
./bin/icingacli setup config webserver apache --document-root /usr/share/icingaweb2/public > /etc/apache2/conf-available/icingaweb2.conf
a2enconf icingaweb2
```

Example for Apache on Alpine Linux:
```bash
icingacli setup config webserver apache --document-root /usr/share/webapps/icingaweb2/public > /etc/apache2/conf.d/icingaweb2.conf
```
### Preparing Icinga Web 2 Setup <a id="preparing-web-setup-from-source"></a>

You can set up Icinga Web 2 quickly and easily with the Icinga Web 2 setup wizard which is available the first time
you visit Icinga Web 2 in your browser. Please follow the steps listed below for preparing the web setup.

Because both web and CLI must have access to configuration and logs, permissions will be managed using a special
system group. The web server user and CLI user have to be added to this system group.

Add the system group `icingaweb2` in the first place.

**Fedora, RHEL, CentOS, SLES and OpenSUSE**:
```bash
groupadd -r icingaweb2
```

**Debian and Ubuntu**:
```bash
addgroup --system icingaweb2
```

Add your web server's user to the system group `icingaweb2`
and restart the web server:

**Fedora, RHEL and CentOS**:
```bash
usermod -a -G icingaweb2 apache
service httpd restart
```

**SLES and OpenSUSE**:
```bash
usermod -A icingaweb2 wwwrun
service apache2 restart
```

**Debian and Ubuntu**:
```bash
usermod -a -G icingaweb2 www-data
service apache2 restart
```

**Alpine Linux**:
```bash
gpasswd -a apache icingaweb2
rc-service apache2 restart
```


Use `icingacli` to create the configuration directory which defaults to **/etc/icingaweb2**:
```bash
./bin/icingacli setup config directory
```


When using the web setup you are required to authenticate using a token. In order to generate a token use the
`icingacli`:
```bash
./bin/icingacli setup token create
```

In case you do not remember the token you can show it using the `icingacli`:
```bash
./bin/icingacli setup token show
```

### Icinga Web 2 Setup Wizard <a id="web-setup-from-source-wizard"></a>

Finally visit Icinga Web 2 in your browser to access the setup wizard and complete the installation:
`/icingaweb2/setup`.

Paste the previously generated token and follow the steps on-screen. Then you are done here.

If you prefer to set up the configuration manually, follow the
[Icinga Web 2 Manual Configuration instructions](20-Advanced-Topics.md#web-setup-manual-from-source-config)
<!-- {% endif %} --><!-- {# end index if #} -->
