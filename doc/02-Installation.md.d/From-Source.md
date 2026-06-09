# Installing Icinga Web from Source

Although the preferred way of installing Icinga Web is to use packages, it is also possible to install Icinga Web
directly from source.

## Installing Requirements from Source <a id="installing-from-source-requirements"></a>

You will need to install certain dependencies depending on your setup:

* [Icinga 2](https://github.com/Icinga/icinga2) and [Icinga DB](https://github.com/Icinga/icingadb) to
  monitor your infrastructure
* A web server, e.g. Apache or Nginx
* PHP version ≥ 8.2
* [Icinga PHP Library (ipl)](https://github.com/Icinga/icinga-php-library) ≥ 1.0.0
* [Icinga PHP Thirdparty](https://github.com/Icinga/icinga-php-thirdparty) ≥ 1.0.0
* The following PHP modules must be installed: cURL, json, gettext, fileinfo, intl, dom, OpenSSL and xml
* The [pdfexport](https://github.com/Icinga/icingaweb2-module-pdfexport) module (≥0.13.0) for PDF export
* LDAP PHP library when using Active Directory or LDAP for authentication
* MariaDB/MySQL or PostgreSQL PHP libraries

## Getting the Source <a id="getting-the-source"></a>

Git clone:

```bash
cd /usr/share/
git clone https://github.com/Icinga/icingaweb2.git icingaweb2
```

Tarball download (latest [release](https://github.com/Icinga/icingaweb2/releases/latest)):

```bash
cd /usr/share/
wget https://github.com/Icinga/icingaweb2/archive/v<VERSION>.tar.gz
tar xzf v<VERSION>.tar.gz
mv icingaweb2-<VERSION> icingaweb2
```

Ensure that `/usr/share/icingaweb2/bin/icingacli` is available in your `PATH`.

## Setting Up Icinga Web <a id="from-source-setup"></a>

Create the `icingaweb2` system group and add your web server's user to it.
The web server user depends on your distribution, e.g. `www-data` on Debian
derivatives, or `apache` on RHEL derivatives.

```bash
groupadd -r icingaweb2
usermod -a -G icingaweb2 <webserver-user>
```

Restart the web server afterwards.

<!-- {% if not icingaDocs %} -->
Continue with [Setting up the Database](../02-Installation.md#setting-up-the-database)
and the steps that follow to complete the installation.
<!-- {% endif %} -->
<!-- {% include "02-Installation.md" %} -->
