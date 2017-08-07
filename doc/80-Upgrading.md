# Upgrading Icinga Web 2 <a id="upgrading"></a>

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
