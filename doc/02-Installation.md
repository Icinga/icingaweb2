<!-- {% if index %} -->
# Installation <a id="installation"></a>

The preferred way of installing Icinga Web is to use the official package repositories depending on which operating
system and distribution you are running.

Before installing Icinga Web, make sure you have installed [Icinga 2](https://icinga.com/docs/icinga-2)
and [Icinga DB](https://icinga.com/docs/icinga-db) to monitor your infrastructure. Additionally,
a web server (e.g. Apache or Nginx), PHP version ≥ 8.2, and a MySQL (≥8.0), MariaDB (≥10.2.2),
or PostgreSQL (≥9.6) database are required.

Optionally: the [pdfexport](https://github.com/Icinga/icingaweb2-module-pdfexport) module (≥0.13.0)
for PDF export, and the LDAP PHP library for Active Directory or LDAP authentication.

Please follow the steps listed for your operating system. Packages for distributions other than the ones
listed here may also be available. Please refer to [icinga.com/get-started/download](https://icinga.com/get-started/download/#community)
for a full list of available community repositories.

## Upgrade <a id="upgrade"></a>

In case you are upgrading from an older version of Icinga Web
please make sure to read the [upgrading](80-Upgrading.md#upgrading) section
thoroughly.

<!-- {% elif not icingaDocs %} -->
## Installing the Package

If the [repository](https://packages.icinga.com) is not configured yet, please add it first.
Then use your distribution's package manager to install the `icingaweb2` package
or install [from source](02-Installation.md.d/From-Source.md).
<!-- {% else %} -->

<!-- {% if rhel or amazon_linux %} -->
!!! note

    If you have SELinux enabled, please ensure to either have the selinux package for Icinga Web installed, or disable it.
<!-- {% endif %} -->

## Setting up the Database <a id="setting-up-the-database"></a>

A MySQL (≥8.0), MariaDB (≥10.2.2), or PostgreSQL (≥9.6) database is required to run Icinga Web.
Please follow the steps listed for your target database,
which guide you through setting up the database and user.

### Setting up a MySQL or MariaDB Database

Set up a MySQL database for Icinga Web:

```
# mysql -u root -p

CREATE DATABASE icingaweb;
CREATE USER 'icingaweb'@'localhost' IDENTIFIED BY 'CHANGEME';
GRANT ALL ON icingaweb.* TO 'icingaweb'@'localhost';
```

### Setting up a PostgreSQL Database

This section walks you through configuring PostgreSQL to work with Icinga Web.

Allow authenticated local sessions for the `icingaweb` database user by modifying
the `pg_hba.conf` file.
The location of this file is operating system specific, but can be queried.

```
su postgres -c "psql -c 'show hba_file;'"
```

For a local database server, add the following entries before any broader
matching rules:

```
local icingaweb icingaweb              scram-sha-256
host  icingaweb icingaweb 127.0.0.1/32 scram-sha-256
host  icingaweb icingaweb      ::1/128 scram-sha-256
```

Use `md5` instead of `scram-sha-256` only with PostgreSQL versions older
than 10.

For a remote database server, make sure PostgreSQL listens on an address
reachable from Icinga Web. Then add `host` entries before any broader
matching rules, only for the Icinga Web server addresses or subnets that
should connect to PostgreSQL. For example, if Icinga Web connects
from `192.0.2.43`:

```
host  icingaweb icingaweb 192.0.2.43/32 scram-sha-256
```

The example below uses the `en_US.UTF-8` locale. This locale must be available
on the PostgreSQL server. Use `locale -a` to list available locale names and
replace `en_US.UTF-8` with the exact UTF-8 locale name on your system, such as
`en_US.utf8`.

To apply all these changes, restart PostgreSQL.

```
systemctl restart postgresql
```

Now proceed with actually creating both user and database.

```
# su -l postgres

createuser -P icingaweb
createdb -E UTF8 --locale en_US.UTF-8 -T template0 -O icingaweb icingaweb
```

You may also create a separate administrative account with all privileges instead.

## Configuring the Web Server <a id="install-the-web-server"></a>

Ensure that you have a web server with PHP installed before proceeding,
such as Apache or Nginx with PHP version ≥ 8.2. Depending on your operating system,
you may need to install and configure the web server separately.
If you want to use Nginx, you must manually create a configuration file using the following command.
Save the output as a new file in the web server configuration directory:

```bash
icingacli setup config webserver nginx --document-root /usr/share/icingaweb2/public
```

## Preparing Web Setup <a id="prepare-web-setup-from-package"></a>

You can set up Icinga Web quickly and easily with the Icinga Web setup wizard which is available the first time
you visit Icinga Web in your browser. When using the web setup you are required to authenticate using a token.
In order to generate a token use the `icingacli`:

```bash
icingacli setup token create
```

In case you do not remember the token you can show it using the `icingacli`:

```bash
icingacli setup token show
```

### Starting Web Setup <a id="start-web-setup-from-package"></a>

Finally visit Icinga Web in your browser to access the setup wizard and complete the installation:
`/icingaweb2/setup`.

!!! hint

    Use the same database, user and password details created above when asked.

The setup wizard automatically detects the required packages. In case one of them is missing,
e.g. a PHP module, please install the package, restart your webserver and reload the setup page.

This concludes the installation. Now proceed with the [configuration](03-Configuration.md).
<!-- {% endif %} --><!-- {# end index if #} -->
