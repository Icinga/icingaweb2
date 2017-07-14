# Resources <a id="resources"></a>

The configuration file `config/resources.ini` contains information about data sources that can be referenced in other
configuration files. This allows you to manage all data sources at one central place, avoiding the need to edit several
different files, when the information about a data source changes.

## Configuration <a id="resources-configuration"></a>

Each section in `config/resources.ini` represents a data source with the section name being the identifier used to
reference this specific data source. Depending on the data source type, the sections define different directives.
The available data source types are *db*, *ldap*, *ssh* and *livestatus* which will described in detail in the following
paragraphs.

### Database <a id="resources-configuration-database"></a>

A Database resource defines a connection to a SQL databases which can contain users and groups
to handle authentication and authorization, monitoring data or user preferences.

| Directive     | Description |
| ------------- | ----------- |
| **type**      | `db` |
| **db**        | Database management system. In most cases `mysql` or `pgsql`. |
| **host**      | Connect to the database server on the given host. For using unix domain sockets, specify `localhost` for MySQL and the path to the unix domain socket directory for PostgreSQL. |
| **port**      | Port number to use. Mandatory for connections to a PostgreSQL database. |
| **username**  | The username to use when connecting to the server. |
| **password**  | The password to use when connecting to the server. |
| **dbname**    | The database to use. |
| **charset**   | The character set to use for the database connection. |

#### Example <a id="resources-configuration-database-example"></a>

```
[icingaweb-mysql-tcp]
type      = db
db        = mysql
host      = 127.0.0.1
port      = 3306
username  = icingaweb
password  = icingaweb
dbname    = icingaweb

[icingaweb-mysql-socket]
type      = db
db        = mysql
host      = localhost
username  = icingaweb
password  = icingaweb
dbname    = icingaweb

[icingaweb-pgsql-socket]
type      = db
db        = pgsql
host      = /var/run/postgresql
port      = 5432
username  = icingaweb
password  = icingaweb
dbname    = icingaweb
```

### LDAP <a id="resources-configuration-ldap"></a>

A LDAP resource represents a tree in a LDAP directory. LDAP is usually used for authentication and authorization.

| Directive         | Description |
| ----------------- | ----------- |
| **type**          | `ldap` |
| **hostname**      | Connect to the LDAP server on the given host. You can also provide multiple hosts separated by a space. |
| **port**          | Port number to use for the connection. |
| **root_dn**       | Root object of the tree, e.g. `ou=people,dc=icinga,dc=org` |
| **bind_dn**       | The user to use when connecting to the server. |
| **bind_pw**       | The password to use when connecting to the server. |
| **encryption**    | Type of encryption to use: `none` (default), `starttls`, `ldaps`. |

#### Example <a id="resources-configuration-ldap-example"></a>

```
[ad]
type        = ldap
hostname    = localhost
port        = 389
root_dn     = "ou=people,dc=icinga,dc=org"
bind_dn     = "cn=admin,ou=people,dc=icinga,dc=org"
bind_pw     = admin
```

### SSH <a id="resources-configuration-ssh"></a>

A SSH resource contains the information about the user and the private key location, which can be used for the key-based
ssh authentication.

| Directive         | Description |
| ----------------- | ----------- |
| **type**          | `ssh` |
| **user**          | The username to use when connecting to the server. |
| **private_key**   | The path to the private key of the user. |

#### Example <a id="resources-configuration-ssh-example"></a>

```

[ssh]
type        = "ssh"
user        = "ssh-user"
private_key = "/etc/icingaweb2/ssh/ssh-user"
```
