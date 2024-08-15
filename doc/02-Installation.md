# Installation <a id="installation"></a>

The preferred way of installing Icinga Web 2 is to use the official package repositories depending on which operating
system and distribution you are running.

In case you are upgrading from an older version of Icinga Web 2
please make sure to read the [upgrading](80-Upgrading.md#upgrading) section
thoroughly.

Source and automated setups are described inside the [advanced topics](20-Advanced-Topics.md#advanced-topics)
chapter.

## Installing Requirements <a id="installing-requirements"></a>

* [Icinga 2](https://icinga.com/products/icinga-2/) with the IDO database backend (MariaDB, MySQL or PostgreSQL)
* A web server, e.g. Apache or Nginx
* PHP version >= 5.6.0
* The following PHP modules must be installed: cURL, gettext, intl, mbstring, OpenSSL and xml
* LDAP PHP library when using Active Directory or LDAP for authentication
* MariaDB / MySQL or PostgreSQL PHP libraries


## Installing Icinga Web 2 from Package <a id="installing-from-package"></a>

Official repositories ([support matrix](https://icinga.com/subscription/support-details/)):

| Distribution  | Repository |
| ------------- | ---------- |
| Debian        | [Icinga Repository](https://packages.icinga.com/debian/) |
| Ubuntu        | [Icinga Repository](https://packages.icinga.com/ubuntu/) |
| RHEL/CentOS   | [Icinga Repository](https://packages.icinga.com/epel/) |
| openSUSE      | [Icinga Repository](https://packages.icinga.com/openSUSE/) |
| SLES          | [Icinga Repository](https://packages.icinga.com/SUSE/) |


Community repositories:

| Distribution  | Repository |
| ------------- | ---------- |
| Gentoo        | [Upstream](https://packages.gentoo.org/packages/www-apps/icingaweb2) |
| FreeBSD       | [Upstream](http://portsmon.freebsd.org/portoverview.py?category=net-mgmt&portname=icingaweb2) |
| ArchLinux     | [Upstream](https://aur.archlinux.org/packages/icingaweb2) |
| Alpine Linux  | [Upstream](https://git.alpinelinux.org/cgit/aports/tree/community/icingaweb2/APKBUILD) |

Packages for distributions other than the ones listed above may also be available.
Please contact your distribution packagers.

### Setting up Package Repositories <a id="package-repositories"></a>

You need to add the Icinga repository to your package management configuration for installing Icinga Web 2.
If you've already configured your OS to use the Icinga repository for installing Icinga 2, you may skip this step.

**Debian**:

```
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

**Ubuntu**:

```
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

**RHEL and CentOS 8**:
```
dnf install https://packages.icinga.com/epel/icinga-rpm-release-8-latest.noarch.rpm
```

**RHEL and CentOS 7**:
```
yum install https://packages.icinga.com/epel/icinga-rpm-release-7-latest.noarch.rpm
```

**Fedora 31**:
```
dnf install https://packages.icinga.com/fedora/icinga-rpm-release-31-latest.noarch.rpm
```

**SLES 15/12**:
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

The packages for RHEL/CentOS depend on other packages which are distributed
as part of the [EPEL repository](https://fedoraproject.org/wiki/EPEL).

CentOS 8 additionally needs the PowerTools repository for EPEL:

```
dnf install 'dnf-command(config-manager)'
dnf config-manager --set-enabled PowerTools

dnf install epel-release
```

CentOS 7:

```
yum install epel-release
```

If you are using RHEL you need to additionally enable the `optional` and `codeready-builder`
repository before installing the [EPEL rpm package](https://fedoraproject.org/wiki/EPEL#How_can_I_use_these_extra_packages.3F).

RHEL 8:

```
ARCH=$( /bin/arch )

subscription-manager repos --enable rhel-8-server-optional-rpms
subscription-manager repos --enable "codeready-builder-for-rhel-8-${ARCH}-rpms"

dnf install https://dl.fedoraproject.org/pub/epel/epel-release-latest-8.noarch.rpm
```

RHEL 7:

```
subscription-manager repos --enable rhel-7-server-optional-rpms
yum install https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm
```

##### RHEL/CentOS 7 PHP SCL

Since version 2.5.0 we also require a **newer PHP version** than what is available
in RedHat itself. You need to enable the SCL repository, so that the dependencies
can pull in the newer PHP.

CentOS:
```
yum install centos-release-scl
```

RedHat:
```
subscription-manager repos --enable rhel-server-rhscl-7-rpms
```

Make sure to also read the chapter on [Setting up FPM](02-Installation.md#setting-up-fpm).

#### Alpine Linux Notes <a id="package-repositories-alpine-notes"></a>

The example provided suppose that you are running Alpine edge, which is the -dev branch and is a rolling release.
If you are using a stable version, in order to use the latest Icinga Web 2 version you should "pin" the edge repository.
In order to correctly manage your repository, please follow
[these instructions](https://wiki.alpinelinux.org/wiki/Alpine_Linux_package_management).

### Installing Icinga Web 2 <a id="installing-from-package-example"></a>

You can install Icinga Web 2 by using your distribution's package manager to install the `icingaweb2` package.
Below is a list with examples for various distributions. The additional package `icingacli` is necessary to follow further steps in this guide.
The additional package `libapache2-mod-php` is necessary on Ubuntu to make
Icinga Web 2 working out-of-the-box if you aren't sure or don't care about [PHP
FPM](02-Installation.md#setting-up-fpm).

**Debian**:
```
apt-get install icingaweb2 icingacli
```

**Ubuntu**:
```
apt-get install icingaweb2 libapache2-mod-php icingacli
```

**RHEL/CentOS 8 and Fedora**:
```
dnf install icingaweb2 icingacli
```

**RHEL/CentOS 7**
```
yum install icingaweb2 icingacli
```

If you have [SELinux](90-SELinux.md) enabled, the package `icingaweb2-selinux` is also required.
For RHEL/CentOS please read the [package repositories notes](02-Installation.md#package-repositories-rhel-notes).

**SLES and openSUSE**:
```
zypper install icingaweb2 icingacli
```

**Alpine Linux**:
```
apk add icingaweb2
```
For Alpine Linux please read the [package repositories notes](02-Installation.md#package-repositories-alpine-notes).

## Installing the web server <a id="installing-the-web-server"></a>

Depending on your OS you might have to install, and or configure the web server.
We usually only require PHP as hard dependency.

We usually build on Apache httpd as the default web server, but you also can use nginx.

**RedHat / CentOS / Fedora**

Make sure to install httpd, start and enable it on boot.
```
yum install httpd

systemctl start httpd.service
systemctl enable httpd.service
```

Note for **EPEL 7 and 8**: Check the [Setting up FPM](02-Installation.md#setting-up-fpm) chapter.

**SUSE SLE / openSUSE**

Make sure that web server is installed, and the required modules are loaded.
```
zypper install apache2

a2enmod rewrite
a2enmod php7

systemctl start apache2.service
systemctl enable apache2.service
```

**Debian / Ubuntu**

Your web server should be up and running after the installation of Icinga Web 2.

### Setting up FPM <a id="setting-up-fpm"></a>

If you are on CentOS / RedHat, or just want to run Icinga Web 2 with PHP-FPM instead
of the Apache module.

| Operating System    | FPM configuration path            |
|---------------------|-----------------------------------|
| RedHat 8            | `/etc/php-fpm.d/`                |
| RedHat 7 (with SCL) | `/etc/opt/rh/rh-php71/php-fpm.d/` |
| Fedora              | `/etc/php-fpm.d/`                 |
| Debian/Ubuntu       | `/etc/php*/*/fpm/pool.d/`         |

The default pool `www` should be sufficient for Icinga Web 2.

On RedHat you need to start and enable the FPM service.

RedHat / CentOS 8 and Fedora:

```
systemctl start php-fpm.service
systemctl enable php-fpm.service
```

RedHat / CentOS 7 (SCL package):
```
systemctl start rh-php71-php-fpm.service
systemctl enable rh-php71-php-fpm.service
```

All module packages for PHP have this SCL prefix, so you can install a
database module like this:
```
yum install rh-php71-php-mysqlnd
# or
yum install rh-php71-php-pgsql
```

Depending on your web server installation, we might have installed or
updated the config file for icingaweb2 with defaults for FPM.

Check `/etc/httpd/conf.d/icingaweb2.conf` or `/etc/apache2/conf.d/icingaweb2.conf`.
And `*.rpm*` `*.dpkg*` files there with updates.

Make sure that the `FilesMatch` part is included for Apache >= 2.4. For Apache < 2.4 you have to include the
`LocationMatch` block.

Also see the example from icingacli:
```
icingacli setup config webserver apache
```

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

#### Preparing Web Setup on Debian/Ubuntu <a id="preparing-web-setup-from-package-debian"></a>

On Debian and derivates, you need to manually create a database and a database user prior to starting the web wizard.
This is due to local security restrictions whereas the web wizard cannot create a database/user through
a local unix domain socket.

```
MariaDB [mysql]> CREATE DATABASE icingaweb2;

MariaDB [mysql]> GRANT ALL ON icingaweb2.* TO icingaweb2@localhost IDENTIFIED BY 'CHANGEME';
```

You may also create a separate administrative account with all privileges instead.

> Note: This is only required if you are using a local database as authentication type.

### Starting Web Setup <a id="starting-web-setup-from-package"></a>

Finally visit Icinga Web 2 in your browser to access the setup wizard and complete the installation:
`/icingaweb2/setup`.

> **Note for Debian/Ubuntu**
>
> Use the same database, user and password details created above when asked.

The setup wizard automatically detects the required packages. In case one of them is missing,
e.g. a PHP module, please install the package, restart your webserver and reload the setup page.

If you have SELinux enabled, please ensure to either have the selinux package for Icinga Web 2
installed, or disable it.


### Upgrading to FPM <a id="upgrading-to-fpm"></a>

Valid for:

* RedHat / CentOS 7

Other distributions are also possible if preferred, but not included here.

Some upgrading work needs to be done manually, while we install PHP FPM
as dependency, you need to start the service, and configure some things.

Please read [Setting up FPM](02-Installation.md#setting-up-fpm) first.

**php.ini settings** you have tuned in the past needs to be migrated to a SCL installation
of PHP.

Check these directories:

* `/etc/php.ini`
* `/etc/php.d/*.ini`

PHP settings should be stored to:

* RedHat / CentOS 7: `/etc/opt/rh/rh-php71/php.d/`

Make sure to **install the required database modules**

RedHat / CentOS 7:
```
yum install rh-php71-php-mysqlnd
# or
yum install rh-php71-php-pgsql
```

After any PHP related change you now need to **restart FPM**:

RedHat / CentOS 7:
```
systemctl restart rh-php71-php-fpm.service
```

If you don't need mod_php for other apps on the server, you should disable it in Apache.

Disable PHP in Apache httpd:
```
cd /etc/httpd
cp conf.d/php.conf{,.bak}
: >conf.d/php.conf

# ONLY on el7!
cp conf.modules.d/10-php.conf{,.bak}
: >conf.modules.d/10-php.conf

systemctl restart httpd.service
```

You can also uninstall the mod_php package, or all non-SCL PHP related packages.
```
yum remove php
# or
yum remove php-common
```

