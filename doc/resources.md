# <a id="resources"></a> Resources

The INI configuration file **config/resources.ini** contains information about data sources that can be referenced in other
configuration files. This allows you to manage all data sources at one central place, avoiding the need to edit several
different files, when the information about a data source changes.

## <a id="resources-configuration"></a> Configuration

Each section in **config/resources.ini** represents a data source with the section name being the identifier used to
reference this specific data source. Depending on the data source type, the sections define different directives.
The available data source types are *db*, *ldap*, *ssh* and *livestatus* which will described in detail in the following
paragraphs.

### <a id="resources-configuration-database"></a> Database

A Database resource defines a connection to a SQL databases which can contain users and groups
to handle authentication and authorization, monitoring data or user preferences.

Directive       | Description
----------------|------------
**type**        | `db`
**db**          | Database management system. Either `mysql` or `pgsql`.
**host**        | Connect to the database server on the given host.
**port**        | Port number to use for the connection.
**username**    | The username to use when connecting to the server.
**password**    | The password to use when connecting to the server.
**dbname**      | The database to use.

**Example:**

```
[icingaweb]
type      = db
db        = mysql
host      = localhost
port      = 3306
username  = icingaweb
password  = icingaweb
dbname    = icingaweb
```

### <a id="resources-configuration-ldap"></a> LDAP

A LDAP resource represents a tree in a LDAP directory. LDAP is usually used for authentication and authorization.

Directive       | Description
----------------|------------
**type**        | `ldap`
**hostname**    | Connect to the LDAP server on the given host.
**port**        | Port number to use for the connection.
**root_dn**     | Root object of the tree, e.g. "ou=people,dc=icinga,dc=org"
**bind_dn**     | The user to use when connecting to the server.
**bind_pw**     | The password to use when connecting to the server.

**Example:**

````
[ad]
type      = ldap
hostname  = localhost
port      = 389
root_dn   = "ou=people,dc=icinga,dc=org"
bind_dn   = "cn=admin,ou=people,dc=icinga,dc=org"
bind_pw   = admin`
````

### <a id="resources-configuration-ssh"></a> SSH

A SSH resource contains the information about the user and the private key location, which can be used for the key-based
ssh authentication.

Directive           | Description
--------------------|------------
**type**            | `ssh`
**user**            | The username to use when connecting to the server.
**private_key**     | The path to the private key of the user.

**Example:**

````
[ssh]
type                = "ssh"
user                = "ssh-user"
private_key        = "/etc/icingaweb2/ssh/ssh-user"
````

### <a id="resources-configuration-livestatus"></a> Livestatus

A Livestatus resource represents the location of a Livestatus socket which is used for fetching monitoring data.

Directive       | Description
----------------|------------
**type**        | `livestatus`
**socket**      | Location of the Livestatus socket. Either a path to a local Livestatus socket or a path to a remote Livestatus socket in the format `tcp://<host>:<port>`.

**Example:**

````
[livestatus]
type    = livestatus
socket  = /var/run/icinga2/cmd/livestatus
````
