# Preferences <a id="preferences"></a>

Preferences are settings a user can set for their account only,
for example the language and time zone.

Preferences can be stored either in a MariaDB, MySQL or in a PostgreSQL database. The database must be configured.

## Configuration <a id="preferences-configuration"></a>

The preference configuration backend is defined in the global [config.ini](03-Configuration.md#configuration-general-global) file.

You have to define a [database resource](04-Resources.md#resources-configuration-database)
which will be referenced as resource for the preferences storage.

You need to add the following section to the global [config.ini](03-Configuration.md#configuration-general-global) file
in order to store preferences in a database.

```
[global]
config_resource = "icingaweb_db"
```
