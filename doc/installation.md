# <a id="installation"></a> Installation

The preferred way of installing Icinga Web 2 is to use the official package repositories depending on which operating
system and distribution you are running. But it is also possible to install Icinga Web 2 directly from source.

## <a id="installation-requirements"></a> Installing Requirements

* A web server, e.g. Apache or nginx
* PHP >= 5.3.0 w/ gettext and OpenSSL support
* MySQL or PostgreSQL PHP libraries when using a database for authentication or storing user preferences into a database
* LDAP PHP library when using Active Directory or LDAP for authentication
* Icinga 1.x w/ Livestatus or IDO, Icinga 2 w/ Livestatus or IDO feature enabled

## <a id="installation-from-package"></a> Installing Icinga Web 2 from Package

A guide on how to install Icinga Web 2 from package will follow shortly.

## <a id="installation-from-source"></a> Installing Icinga Web 2 from Source

**Step 1: Getting the Source**

First of all, you need to download the sources. Icinga Web 2 is available through a Git repository. You can clone this
repository either via git or http protocol using the following URLs:

  * git://git.icinga.org/icingaweb2.git
  * http://git.icinga.org/icingaweb2.git

There is also a browsable version available at
[git.icinga.org](https://git.icinga.org/?p=icingaweb2.git;a=summary "Icinga Web 2 Git Repository").
This version also offers snapshots for easy download which you can use if you do not have git present on your system.

````
git clone git://git.icinga.org/icingaweb2.git
````

**Step 2: Install the Source**

Choose a target directory and move Icinga Web 2 there.

````
mv icingaweb2 /usr/share/icingaweb2
````

**Step 3: Configuring the Web Server**

Use `icingacli` to generate web server configuration for either Apache or nginx.

Apache:
````
./bin/icingacli setup config webserver apache --document-root /usr/share/icingaweb2/public
````

nginx:
````
./bin/icingacli setup config webserver nginx --document-root /usr/share/icingaweb2/public
````

Save the output as new file in your webserver's configuration directory.

Example for Apache on RHEL/CentOS:
````
./bin/icingacli setup config webserver apache --document-root /usr/share/icingaweb2/public > /etc/httpd/conf.d/icingaweb2.conf
````


**Step 4: Preparing Web Setup**

Because both web and CLI must have access to configuration and logs, permissions will be managed using a special
system group. The web server user and CLI user have to be added to this system group.

Add the system group `icingaweb2` in the first place.

Fedora, RHEL, CentOS, SLES and OpenSUSE:
````
groupadd -r icingaweb2
````

Debian and Ubuntu:
````
addgroup --system icingaweb2
````

Add your web server's user to the system group `icingaweb2`:

Fedora, RHEL and CentOS:
````
usermod -a -G icingaweb2 apache
````

SLES and OpenSUSE:
````
usermod -A icingaweb2 wwwrun
````

Debian and Ubuntu:
````
usermod -a -G icingaweb2 www-data
````

Use `icingacli` to create the configuration directory which defaults to **/etc/icingaweb2**:
````
./bin/icingacli setup config directory
````

When using the web setup you are required to authenticate using a token. In order to generate a token use the
`icingacli`:
````
./bin/icingacli setup token create
````

In case you do not remember the token you can show it using the `icingacli`:
````
./bin/icingacli setup token show
````

**Step 5: Web Setup**

Visit Icinga Web 2 in your browser and complete installation using the web setup: /icingaweb2/setup

## Upgrading to Icinga Web 2 Beta 2

Icinga Web 2 Beta 2 introduces access control based on roles for secured actions. If you've already set up Icinga Web 2,
you are required to create the file **roles.ini** beneath Icinga Web 2's configuration directory with the following
content:
````
[administrators]
users = "your_user_name, another_user_name"
permissions = "*"
````

After please log out from Icinga Web 2 and log in again for having all permissions granted.

If you delegated authentication to your web server using the `autologin` backend, you have to switch to the `external`
authentication backend to be able to log in again. The new name better reflects what’s going on. A similar change
affects environments that opted for not storing preferences, your new backend is `none`.

## Upgrading to Icinga Web 2 Beta 3

Because Icinga Web 2 Beta 3 does not introduce any backward incompatible change you don't have to change your
configuration files after upgrading to Icinga Web 2 Beta 3.
