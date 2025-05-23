# Upgrading Icinga Web 2 <a id="upgrading"></a>

Specific version upgrades are described below. Please note that upgrades are incremental. An upgrade from
v2.6 to v2.8 requires to follow the instructions for v2.7 too.

## Upgrading to Icinga Web 2.13

**Breaking changes**

* The following columns of the `Servicestatus` table, which previously displayed the date time (string) as a fetched value, now display the unix timestamp to support relative time filters:
    * `service_last_time_ok`
    * `service_last_time_unknown`
    * `service_last_time_warning`
    * `service_last_time_critical`

## Upgrading to Icinga Web 2.12.2

**Framework changes affecting third-party code**

* `Icinga\Module\Monitoring\Hook\PluginOutputHook` When rendering the Icinga check output, the output and long_output fields are now concatenated with a newline (\n) before any post-processing occurs, such as through a PluginOutputHook

## Upgrading to Icinga Web 2.12.0

**Database Schema**

With the latest Icinga Web versions, you no longer need to manually import sql upgrade scripts. Icinga Web `>= 2.12`
offers you the possibility to perform such migrations in an easy way. You can find and apply all pending migrations
of your Icinga Web environment in the menu at `System -> Migrations`.

You can still apply the `2.12.0.sql` upgrade script manually, depending on your database vendor.
For package installations you can find this file in `/usr/share/icingaweb2/schema/*-upgrades/`.

## Upgrading to Icinga Web 2.11.x

**General**

* Support for Internet Explorer 11 has been removed.
* The Vagrant file and all its assets have been removed.

**Database Schema**

* Please apply the `v2.11.0.sql` upgrade script depending on your database vendor.
  As of version `2.11.4`, upgrade scripts can be found at `/usr/share/icingaweb2/schema/*-upgrades/`.
  Older versions install these files to `/usr/share/doc/icingaweb2/schema/*-upgrades/` for RPM-based systems 
  and `/usr/share/icingaweb2/etc/schema/*-upgrades/` for Debian or Ubuntu.

**Breaking changes**

* The `user:local_name` macro in restrictions has been removed. Use `user.local_name` now.
* User preferences stored in INI files are not loaded anymore. Migrate yours with
  `icingacli migrate preferences` before the upgrade, if you haven't already.

**Framework changes affecting third-party code**

* When loading library CSS assets, CSS files and LESS files are handled differently now. Only the latter
  is parsed as LESS.
* jQuery is not bundled anymore as it's now part of the library icinga-php-thirdparty v0.11.0. It's shipped there
  in version 3.6.0. (Previously bundled was jQuery 3.4.1)
* All the following classes have been removed:
  * `Icinga\User\Preferences\Store\IniStore`: Preferences in INI files are not supported anymore.
  * `Icinga\User\Preferences\Store\DbStore`: Its methods have been added to the `PreferencesStore` class.
  * `Icinga\Util\String`: Use `Icinga\Util\StringHelper` instead.
  * `Icinga\Util\Translator`: Use `\ipl\I18n\StaticTranslator::$instance` or `\ipl\I18n\Translation` instead.
  * `Icinga\Module\Migrate\Clicommands\DashboardCommand`: Deleted without substitution.
  * `Icinga\Web\Hook\TicketHook`: Use `Icinga\Application\Hook\TicketHook` instead.
  * `Icinga\Web\Hook\GrapherHook`: Use `Icinga\Application\Hook\GrapherHook` instead.
  * `Icinga\Module\Monitoring\Environment`: Not in use.
  * `Icinga\Module\Monitoring\Backend`: Use `Icinga\Module\Monitoring\Backend\MonitoringBackend` instead.
* All the following methods have been removed:
  * `loader.js.addUrlFlag()`: Use `Icinga.Utils.addUrlFlag()` instead.
  * `Url::setBaseUrl()`: Please create a new url from scratch instead.
  * `Url::getBaseUrl()`: Use either `Url::getBasePath()` or `Url::getAbsoluteUrl()` now.
  * `ApplicationBootstrap::setupZendAutoloader()`: Since it does nothing, all usages removed.
  * `ApplicationBootstrap::listLocales()`: Use `\ipl\I18n\GettextTranslator::listLocales()` instead.
  * `Module::registerHook()`: Use `provideHook()` instead.
  * `Web::getMenu()`: Instantiate the menu class `new Menu()` directly instead.
  * `AesCrypt::encryptToBase64()`: Use `AesCrypt::encrypt()` instead as it also returns a base64 encoded string.
  * `AesCrypt::decryptFromBase64()`: Use `AesCrypt::decrypt()` instead as it also returns a base64 decoded string.
  * `InlinePie::disableNoScript()`: Empty method.
  * `SimpleQuery::paginate()`: Use `Icinga\Web\Controller::setupPaginationControl()` and/or `Icinga\Web\Widget\Paginator` instead.
  * `LdapConnection::connect()`: The connection is established lazily since .. a long time.
  * `MonitoredObject::matches()`: Use `$filter->matches($object)` instead.
  * `MonitoredObject::fromParams()`: Deleted without substitution.
  * `DataView::fromRequest()`: Use `$backend->select()->from($viewName)` instead.
  * `DataView::sort()`: Use `DataView::order()` instead.
  * `MonitoringBackend::createBackend()`: Use `MonitoringBackend::instance()` instead.
  * `DbConnection::getConnection()`: Use `Connection::getDbAdapter()` instead.
  * `DbQuery::renderFilter()`: Use `DbConnection::renderFilter()` instead.
  * `DbQuery::whereToSql()`: Use `DbConnection::renderFilter()` instead.

## Upgrading to Icinga Web 2 2.10.x

**General**

* The theme "solarized-dark" has been removed due to the introduction of the new default dark mode.

**Deprecations**

* Builtin support for PDF exports using the `dompdf` library will be dropped with version 2.12.
  It is highly recommended to use [Icinga PDF Export](https://github.com/Icinga/icingaweb2-module-pdfexport)
  instead.

**Discontinued package updates**

* We will stop offering major updates for Debian 9 (Stretch) starting with version 2.11.
  However, versions 2.9 and 2.10 will continue to receive minor updates on this platform.

[icinga.com](https://icinga.com/subscription/support-details/) provides an overview about
currently supported distributions.

**Framework changes affecting third-party code**

* Asset support for modules (#3961) introduced with v2.8 has now been removed.
* `expandable-toggle`-support has been removed. Use `class="collapsible" data-visible-height=0`
  to achieve the same effect. (Available since v2.7.0)
* The `.var()` LESS mixin and the LESS function `extract-variable-default` have been removed (introduced with v2.9)

## Upgrading to Icinga Web 2 2.9.1

**Database Schema**

* Please apply the `v2.9.1.sql` upgrade script depending on your database vendor.
  In package installations this file can be found in `/usr/share/doc/icingaweb2/schema/*-upgrades/`
  (Debian/Ubuntu: `/usr/share/icingaweb2/etc/schema/*-upgrades/`).

## Upgrading to Icinga Web 2 2.9.x

**Installation**

* Icinga Web 2 now requires the [Icinga PHP Library (ipl)](https://github.com/Icinga/icinga-php-library) (>= 0.6)
  and [Icinga PHP Thirdparty](https://github.com/Icinga/icinga-php-thirdparty) (>= 0.10). Please make sure to
  install both when upgrading. We provide packages for them and if you've installed Icinga Web 2 already by
  package they should be installed automatically during the upgrade.
* [Icinga Business Process Modelling](https://github.com/Icinga/icingaweb2-module-businessprocess/releases/tag/v2.3.1)
  has been updated to v2.3.1. If you're using this module, this version is required when upgrading.

**General**

* For database connections to the IDO running on MySQL, a default charset (`latin1`) is now applied.
  If you had previously problems with special characters and umlauts and you've set this charset
  already manually, no change is required. However, if your IDO resource configuration has another
  charset configured than this, it is highly recommended to clear this setting. Otherwise the default
  won't apply and characters may still be shown incorrectly in the UI.

**Database Schema**

* Icinga Web 2 now permits its users to stay logged in. This requires a new database table.
  * Please apply the `v2.9.0.sql` upgrade script depending on your database vendor.
    In package installations this file can be found in `/usr/share/doc/icingaweb2/schema/*-upgrades/`
    (Debian/Ubuntu: `/usr/share/icingaweb2/etc/schema/*-upgrades/`).

**Breaking changes**

* Password changes are not allowed by default anymore
  * The fake refusal `no-user/password-change` has now been changed to a grant `user/password-change`.
    Any user that had `no-user/password-change` previously still cannot change passwords. Though any
    user that didn't have this *permission*, needs to be granted `user/password-change` now in order
    to change passwords.

**Deprecations**

* Support for EOL PHP versions (5.6, 7.0, 7.1 and 7.2) will be removed with version 2.11
* Support for Internet Explorer will be completely removed with version 2.11
  * New features after v2.9 will already not (necessarily) be available in Internet Explorer
* `user.local_name` replaces the `user:local_name` macro in restrictions, and the latter will be removed with
  version 2.11
* The configuration backend type `INI` is not configurable anymore. **A database is now mandatory.**
  * Existing configurations using this configuration backend type will stop working with the
    release of v2.11.
  * To migrate your local user preferences to database, enable the `migrate` module and use the command
    `icingacli migrate preferences`. If you already setup the configuration database, it will work right
    away. If not, pass it the resource you'd like to use as configuration database with `--resource=`.
  * Note that this only applies to user preferences. Other configurations are still stored
    in `.ini` files. (#3770)
* The Vagrant file and all its assets will be removed with version 2.11

**Framework changes affecting third-party code**

* The `jquery-migrate` compatibility layer for Javascript code working with jQuery 2.x has been removed.
  It has been introduced with v2.7 when we upgraded jQuery to v3.4.1 in order to allow module developers
  a seamless upgrade chance. If a module still has UI glitches after an upgrade to v2.9, please contact
  the module developer.
* The method `getHtmlForEvent` of the `EventDetailsExtensionHook` previously received the host or service
  object of an event. Now the actual event object is passed to it instead.
* Asset support for modules (#3961) introduced with v2.8 has now been deprecated in favor of library
  support (#4272) and will be removed with v2.10. We don't expect broad usage of this feature since
  it's been introduced with the latest major version, so it's already being removed with the next one.

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
**/usr/share/doc/icingaweb2/schema/mysql-upgrades** (Debian/Ubuntu: **/usr/share/icingaweb2/etc/schema/mysql-upgrades**)
to update your database schema.
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
**/usr/share/doc/icingaweb2/schema/pgsql-upgrades** (Debian/Ubuntu: **/usr/share/icingaweb2/etc/schema/pgsql-upgrades**)
to update your database schema.
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
