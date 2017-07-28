# Preferences <a id="preferences"></a>

Preferences are settings a user can set for his account only, for example his language and time zone.

**Choosing Where to Store Preferences**

Preferences can be stored either in INI files or in a MySQL or in a PostgreSQL database. By default, Icinga Web 2 stores
preferences in INI files beneath Icinga Web 2's configuration directory.

## Configuration <a id="preferences-configuration"></a>

Where to store preferences is defined in the INI file **config/config.ini** in the *preferences* section.

### Store Preferences in INI Files <a id="preferences-configuration-ini"></a>

If preferences are stored in INI Files, Icinga Web 2 automatically creates one file per user using the username as
file name for storing preferences. A INI file is created once a user saves changed preferences the first time.
The files are located beneath the `preferences` directory beneath Icinga Web 2's configuration directory.

For storing preferences in INI files you have to add the following section to the INI file **config/config.ini**:

```
[preferences]
type = ini
```

### Store Preferences in a Database <a id="preferences-configuration-db"></a>

In order to be more flexible in distributed setups you can store preferences in a MySQL or in a PostgreSQL database.
For storing preferences in a database, you have to define a [database resource](04-Resources.md#resources-configuration-database)
which will be referenced as resource for the preferences storage.

| Directive     | Description |
| ------------- | ----------- |
| **type**      | `db` |
| **resource**  | The name of the database resource defined in [resources.ini](04-Resources.md#resources). |

**Example:**

```
[preferences]
type     = db
resource = icingaweb-mysql
```

#### Database Setup <a id="preferences-configuration-db-setup"></a>

For storing preferences in a database, you have to import one of the following database schemas:

* **etc/schema/preferences.mysql.sql** (for **MySQL** database)
* **etc/schema/preferences.pgsql.sql** (for **PostgreSQL** databases)

After that you have to define the [database resource](04-Resources.md#resources-configuration-database).
