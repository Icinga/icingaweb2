# Installation <a id="installation"></a>

The preferred way of installing Icinga Web 2 is to use the official package repositories depending on which operating
system and distribution you are running.

In case you are upgrading from an older version of Icinga Web 2
please make sure to read the [upgrading](80-Upgrading.md#upgrading) section
thoroughly.

Source and automated setups are described inside the [advanced topics](20-Advanced-Topics.md#advanced-topics)
chapter.

## Installing Requirements <a id="installing-requirements"></a>

* A web server, e.g. Apache or nginx
* PHP >= 5.3.0 w/ gettext, intl, mbstring and OpenSSL support
* Default time zone configured for PHP in the php.ini file
* LDAP PHP library when using Active Directory or LDAP for authentication
* Icinga 2.x w/ IDO feature enabled or Icinga 1.x w/ IDO
* The IDO table prefix must be `icinga_` which is the default
* MySQL or PostgreSQL PHP libraries
* cURL PHP library when using the Icinga 2 API for transmitting external commands


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

**Debian Stretch**:
```
wget -O - http://packages.icinga.com/icinga.key | apt-key add -
echo 'deb http://packages.icinga.com/debian icinga-stretch main' >/etc/apt/sources.list.d/icinga.list
apt-get update
```
> INFO
>
> For other Debian versions just replace `stretch` with your distribution's code name.

**Ubuntu Xenial**:
```
wget -O - http://packages.icinga.com/icinga.key | apt-key add -
add-apt-repository 'deb http://packages.icinga.com/ubuntu icinga-xenial main'
apt-get update
```
> INFO
>
> For other Ubuntu versions just replace xenial with your distribution's code name.

**RHEL and CentOS 7**:
```
yum install https://packages.icinga.com/epel/icinga-rpm-release-7-latest.noarch.rpm
```

**Fedora 26**:
```
dnf install https://packages.icinga.com/fedora/icinga-rpm-release-26-latest.noarch.rpm
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

The packages for RHEL/CentOS depend on other packages which are distributed
as part of the [EPEL repository](https://fedoraproject.org/wiki/EPEL).

CentOS 7/6:
```
yum install epel-release
```

If you are using RHEL you need enable the `optional` repository and then install
the [EPEL rpm package](https://fedoraproject.org/wiki/EPEL#How_can_I_use_these_extra_packages.3F).


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

#### Preparing Web Setup on Debian <a id="preparing-web-setup-from-package-debian"></a>

On Debian, you need to manually create a database and a database user prior to starting the web wizard.
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

Note for Debian: Use the same database, user and password details created above when asked.


