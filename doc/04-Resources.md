# Resources <a id="resources"></a>

The configuration file `resources.ini` contains information about data sources that can be referenced in other
configuration files. This allows you to manage all data sources at one central place, avoiding the need to edit several
different files when the information about a data source changes.

## Configuration <a id="resources-configuration"></a>

Each section in `resources.ini` represents a data source with the section name being the identifier used to
reference this specific data source. Depending on the data source type, the sections define different directives.
The available data source types are `db`, `ldap` and `ssh` which will described in detail in the following
paragraphs.

Type                     | Description
-------------------------|-----------------------------------------------
db                       | A [database](04-Resources.md#resources-configuration-database) resource (e.g. Icinga 2 DB IDO or Icinga Web 2 user preferences)
ldap                     | An [LDAP](04-Resources.md#resources-configuration-ldap) resource for authentication.
ssh                      | Manage [SSH](04-Resources.md#resources-configuration-ssh) keys for remote access (e.g. command transport).


### Database <a id="resources-configuration-database"></a>

A Database resource defines a connection to a SQL database which
can contain users and groups to handle authentication and authorization, monitoring data or user preferences.

Option                              | Description
------------------------------------|------------
type                                | **Required.** Specifies the resource type. Must be set to `db`.
db                                  | **Required.** Database type. In most cases `mysql` or `pgsql`.
host                                | **Required.** Connect to the database server on the given host. For using unix domain sockets, specify `localhost` for MySQL and the path to the unix domain socket directory for PostgreSQL.
port                                | **Required.** Port number to use. MySQL defaults to `3306`, PostgreSQL defaults to `5432`. Mandatory for connections to a PostgreSQL database.
username                            | **Required.** The database username.
password                            | **Required.** The database password.
dbname                              | **Required.** The database name.
charset                             | **Optional.** The character set for the database connection.
ssl\_do\_not\_verify\_server\_cert  | **Optional.** Disable validation of the server certificate. Only available for the `mysql` database and on PHP versions > 5.6.
ssl\_cert                           | **Optional.** The file path to the SSL certificate. Only available for the `mysql` database.
ssl\_key                            | **Optional.** The file path to the SSL key. Only available for the `mysql` database.
ssl\_ca                             | **Optional.** The file path to the SSL certificate authority. Only available for the `mysql` database.
ssl\_capath                         | **Optional.** The file path to the directory that contains the trusted SSL CA certificates, which are stored in PEM format.Only available for the `mysql` database.
ssl\_cipher                         | **Optional.** A list of one or more permissible ciphers to use for SSL encryption, in a format understood by OpenSSL. For example: `DHE-RSA-AES256-SHA:AES128-SHA`. Only available for the `mysql` database.


#### Example <a id="resources-configuration-database-example"></a>

The name in brackets defines the resource name.

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

A LDAP resource represents a tree in a LDAP directory.
LDAP is usually used for authentication and authorization.

Option                   | Description
-------------------------|-----------------------------------------------
type                     | **Required.** Specifies the resource type. Must be set to `ldap`.
hostname                 | **Required.** Connect to the LDAP server on the given host. You can also provide multiple hosts separated by a space.
port                     | **Required.** Port number to use for the connection.
root\_dn                 | **Required.** Root object of the tree, e.g. `ou=people,dc=icinga,dc=org`.
bind\_dn                 | **Required.** The user to use when connecting to the server.
bind\_pw                 | **Required.** The password to use when connecting to the server.
encryption               | **Optional.** Type of encryption to use: `none` (default), `starttls`, `ldaps`.
timeout                  | **Optional.** Connection timeout for every LDAP connection. Defaults to `5`.
disable_server_side_sort | **Optional.** Disable server side sorting. Defaults to automatic detection whether the server supports this.

#### Server Side Sorting <a id="ldap-server-side-sort"></a>

Icinga Web automatically detects whether the LDAP server supports server side sorting.
If that is not the case, results get sorted on the client side.
There are LDAP servers though which report that they support this feature in general but have it disabled for certain
fields. This may lead to failures. With `disable_server_side_sort` it is possible to disable server side sorting and it
has precedence over the automatic detection.

#### Example <a id="resources-configuration-ldap-example"></a>

The name in brackets defines the resource name.

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

Option                   | Description
-------------------------|-----------------------------------------------
type                     | **Required.** Specifies the resource type. Must be set to `ssh`.
user                     | **Required.** The username to use when connecting to the server.
private\_key             | **Required.** The path to the private key of the user.

#### Example <a id="resources-configuration-ssh-example"></a>

The name in brackets defines the resource name.

```
[ssh]
type        = "ssh"
user        = "ssh-user"
private_key = "/etc/icingaweb2/ssh/ssh-user"
```
