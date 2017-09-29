# Preferences <a id="preferences"></a>

Preferences are settings a user can set for their account only,
for example the language and time zone.

Preferences can be stored either in INI files or in a MySQL or in a PostgreSQL database. By default, Icinga Web 2 stores
preferences in INI files beneath Icinga Web 2's configuration directory.

```
/etc/icingaweb2/<username>/config.ini
```

## Configuration <a id="preferences-configuration"></a>

The preference configuration backend is defined in the global [config.ini](03-Configuration.md#configuration-general-global) file.

### Store Preferences in INI Files <a id="preferences-configuration-ini"></a>

If preferences are stored in INI Files, Icinga Web 2 automatically creates one file per user using the username as
file name for storing preferences. A INI file is created once a user saves changed preferences the first time.
The files are located beneath the `preferences` directory beneath Icinga Web 2's configuration directory.

You need to add the following section to the global [config.ini](03-Configuration.md#configuration-general-global) file
in order to store preferences in a file.

```
[global]
config_backend = "ini"
```

### Store Preferences in a Database <a id="preferences-configuration-db"></a>

In order to be more flexible in distributed setups you can store preferences in a MySQL or in a PostgreSQL database.
For storing preferences in a database, you have to define a [database resource](04-Resources.md#resources-configuration-database)
which will be referenced as resource for the preferences storage.

You need to add the following section to the global [config.ini](03-Configuration.md#configuration-general-global) file
in order to store preferences in a database.

```
[global]
config_backend = "db"
config_resource = "icingaweb_db"
```
