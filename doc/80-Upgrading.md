# Upgrading Icinga Web 2 <a id="upgrading"></a>

Specific version upgrades are described below. Please note that upgrades are incremental. An upgrade from
v2.6 to v2.8 requires to follow the instructions for v2.7 too.

## Upgrading to Icinga Web 2 2.8.x

**Changes in packaging and dependencies**

Valid for distributions:

* RHEL / CentOS 7
  * Upgrade to PHP 7.3 via RedHat SCL

After upgrading to version 2.8.0 you'll get the new `rh-php73` dependency installed. This is a drop-in replacement
for the previous `rh-php71` dependency and only requires the setup of a new fpm service and possibly some copying
of customized configurations.

**php.ini or php-fpm settings** you have tuned in the past need to be copied over to the new path:

From `/etc/opt/rh/rh-php71/` to `/etc/opt/rh/rh-php73/`.

Don't forget to also install any additional **php-modules** for PHP 7.3 you've had previously installed
for e.g. Icinga Web 2 modules.

There's also a new **service** included which replaces the previous one for php-fpm:

Stop the old service: `systemctl stop rh-php71-php-fpm.service`  
Start the new service: `systemctl start rh-php73-php-fpm.service`

You can now safely remove the previous dependency if you like:

`yum remove rh-php71*`

**Discontinued package updates**

Icinga Web 2 v2.8+ is not supported on these platforms:

* RHEL / CentOS 6
* Debian 8 Jessie
* Ubuntu 16.04 LTS (Xenial Xerus)

Please consider an upgrade of your central Icinga system to a newer distribution release.

[icinga.com](https://icinga.com/subscription/support-details/) provides an overview about
currently supported distributions.

**Framework changes affecting third-party code**

* Url parameter `view=compact` is now deprecated. `showCompact` should be used instead.
  Details are in pull request [#4164](https://github.com/Icinga/icingaweb2/pull/4164).
* Form elements of type checkbox now need to be checked prior submission if they're
  required. Previously setting `required => true` didn't cause the browser to complain
  if such a checkbox wasn't checked. Browsers now do complain if so.
* The general layout now uses flexbox instead of fixed positioning. This applies to the
  `#header`, `#sidebar`, `#main`, `#footer`, `#col1`, `#col2` and a column's controls.
  `#sidebar` and `#main` are now additionally wrapped in a new container `#content-wrapper`.

## Upgrading to Icinga Web 2 2.7.x <a id="upgrading-to-2.7.x"></a>

**Breaking changes**

* We've upgraded jQuery to version 3.4.1. If you're a module developer, please add `?_dev` to your address bar to check
  for log messages emitted by jquery-migrate. (https://github.com/jquery/jquery-migrate) Your javascript code will still
  work, though jquery-migrate will notify you if you're utilizing deprecated/removed functions. jquery-migrate will be
  removed with Icinga Web v2.8 and code not adjusted accordingly will stop working.
* If you're using a language other than english and you've adjusted or disabled module dashboards, you'll need to
  update all of your `dashboard.ini` files. A CLI command exists to assist you with this task. Enable the `migrate`
  module and run the following on the host where these files exist: `icingacli migrate dashboard sections --verbose`

## Upgrading to Icinga Web 2 2.6.x <a id="upgrading-to-2.6.x"></a>

* Icinga Web 2 version 2.6.x does not introduce any backward incompatible change.

## Upgrading to Icinga Web 2 2.5.x <a id="upgrading-to-2.5.x"></a>

> **Attention**
>
> Icinga Web 2 v2.5 requires **at least PHP 5.6**.

**Breaking changes**

* Hash marks (`#`) in INI files are no longer recognized as comments by
  [parse_ini_file](https://secure.php.net/manual/en/function.parse-ini-file.php) since PHP 7.0.
* Existing sessions of logged-in users do no longer work as expected due to a change in the `User` data structure.
  Everyone who was logged in before the upgrade has to log out once.

**Changes in packaging and dependencies**

Valid for distributions:

* RHEL / CentOS 6 + 7
  * Upgrading to PHP 7.0 / 7.1 via RedHat SCL (new dependency)
  * See [Upgrading to FPM](02-Installation.md#upgrading-to-fpm) for manual steps that
    are required
* SUSE SLE 12
  * Upgrading PHP to >= 5.6.0 via the alternative packages.
    You might have to confirm the replacement of PHP < 5.6 - but that
    should work with any other PHP app as well.
  * Make sure to enable the new Apache module `a2enmod php7` and restart `apache2`

**Discontinued package updates**

Icinga Web 2 v2.5+ is not supported on these platforms:

* Debian 7 wheezy
* Ubuntu 14.04 LTS (trusty)
* SUSE SLE 11 (all service packs)

Please consider an upgrade of your central Icinga system to a newer distribution release.

[packages.icinga.com](https://packages.icinga.com) provides an overview about currently supported distributions.

**Database schema**

Icinga Web 2 v2.5.0 requires a schema update for the database. The database schema has been adjusted to support
usernames up to 254 characters. This is necessary to support the new domain-aware authentication feature.

Continue here for [MySQL](80-Upgrading.md#upgrading-mysql-db) and [PostgreSQL](80-Upgrading.md#upgrading-pgsql-db).

## Upgrading to Icinga Web 2 2.4.x <a id="upgrading-to-2.4.x"></a>

* Icinga Web 2 version 2.4.x does not introduce any backward incompatible change.

## Upgrading to Icinga Web 2 2.3.x <a id="upgrading-to-2.3.x"></a>

* Icinga Web 2 version 2.3.x does not introduce any backward incompatible change.

## Upgrading to Icinga Web 2 2.2.0 <a id="upgrading-to-2.2.0"></a>

* The menu entry `Authorization` beneath `Config` has been renamed to `Authentication`. The role, user backend and user
  group backend configuration which was previously found beneath `Authentication` has been moved to `Application`.
  
## Upgrading to Icinga Web 2 2.1.x <a id="upgrading-to-2.1.x"></a>

* Since Icinga Web 2 version 2.1.3 LDAP user group backends respect the configuration option `group_filter`.
  Users who changed the configuration manually and used the option `filter` instead
  have to change it back to `group_filter`.

## Upgrading to Icinga Web 2 2.0.0 <a id="upgrading-to-2.0.0"></a>

* Icinga Web 2 installations from package on RHEL/CentOS 7 now depend on `php-ZendFramework` which is available through
  the [EPEL repository](https://fedoraproject.org/wiki/EPEL). Before, Zend was installed as Icinga Web 2 vendor library
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

## Upgrading to Icinga Web 2 Release Candidate 1 <a id="upgrading-to-rc1"></a>

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

## Upgrading to Icinga Web 2 Beta 3 <a id="upgrading-to-beta3"></a>

Because Icinga Web 2 Beta 3 does not introduce any backward incompatible change you don't have to change your
configuration files after upgrading to Icinga Web 2 Beta 3.

## Upgrading to Icinga Web 2 Beta 2 <a id="upgrading-to-beta2"></a>

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

## Upgrading the MySQL Database <a id="upgrading-mysql-db"></a>

If you installed Icinga Web 2 from package, please check the upgrade scripts located in
**/usr/share/doc/icingaweb2/schema/mysql-upgrades** to update your database schema.
In case you installed Icinga Web 2 from source, please find the upgrade scripts in **etc/schema/mysql-upgrades**.

> **Note**
>
> If there isn't an upgrade file for your current version available, there's nothing to do.

Apply all database schema upgrade files incrementally.

```
# mysql -u root -p icingaweb2 < /usr/share/doc/icingaweb2/schema/mysql-upgrades/<version>.sql
```

**Example:** You are upgrading Icinga Web 2 from version `2.4.0` to `2.5.0`. Look into
the `upgrade` directory:

```
$ ls /usr/share/doc/icingaweb2/schema/mysql-upgrades/
2.0.0beta3-2.0.0rc1.sql  2.5.0.sql
```

The upgrade file `2.5.0.sql` must be applied for the v2.5.0 release. If there are multiple
upgrade files involved, apply them incrementally.

```
# mysql -u root -p icinga < /usr/share/doc/icingaweb2/schema/mysql-upgrades/2.5.0.sql
```

## Upgrading the PostgreSQL Database <a id="upgrading-pgsql-db"></a>

If you installed Icinga Web 2 from package, please check the upgrade scripts located in
**/usr/share/doc/icingaweb2/schema/pgsql-upgrades** to update your database schema.
In case you installed Icinga Web 2 from source, please find the upgrade scripts in **etc/schema/pgsql-upgrades**.

> **Note**
>
> If there isn't an upgrade file for your current version available, there's nothing to do.

Apply all database schema upgrade files incrementally.

```
# export PGPASSWORD=icingaweb2
# psql -U icingaweb2 -d icingaweb2 < /usr/share/doc/icingaweb2/schema/pgsql-upgrades/<version>.sql
```

**Example:** You are upgrading Icinga Web 2 from version `2.4.0` to `2.5.0`. Look into
the `upgrade` directory:

```
$ ls /usr/share/doc/icingaweb2/schema/pgsql-upgrades/
2.0.0beta3-2.0.0rc1.sql  2.5.0.sql
```

The upgrade file `2.5.0.sql` must be applied for the v2.5.0 release. If there are multiple
upgrade files involved, apply them incrementally.

```
# export PGPASSWORD=icingaweb2
# psql -U icingaweb2 -d icingaweb2 < /usr/share/doc/icingaweb2/schema/pgsql-upgrades/2.5.0.sql
```
