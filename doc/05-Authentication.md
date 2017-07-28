# Authentication <a id="authentication"></a>

**Choosing the Authentication Method**

With Icinga Web 2 you can authenticate against Active Directory, LDAP, a MySQL or a PostgreSQL database or delegate
authentication to the web server.

Authentication methods can be chained to set up fallback authentication methods
or if users are spread over multiple places.

## Configuration <a id="authentication-configuration"></a>

Authentication methods are configured in the INI file **config/authentication.ini**.

Each section in the authentication configuration represents a single authentication method.

The order of entries in the authentication configuration determines the order of the authentication methods.
If the current authentication method errors or if the current authentication method does not know the account being
authenticated, the next authentication method will be used.

## External Authentication <a id="authentication-configuration-external-authentication"></a>

For delegating authentication to the web server simply add `autologin` to your authentication configuration:

```
[autologin]
backend = external
```

If your web server is not configured for authentication though, the `autologin` section has no effect.

### Example Configuration for Apache and Basic Authentication <a id="authentication-configuration-external-authentication-example"></a>

The following example will show you how to enable external authentication in Apache
using **Basic access authentication**.

**Creating Users**

To create users for **basic access authentication** you can use the tool `htpasswd`. In this example **.http-users** is
the name of the file containing the user credentials.

The following command creates a new file with the user **icingaadmin**. `htpasswd` will prompt you for a password.
If you want to add more users to the file you have to omit the `-c` switch to not overwrite the file.

```
sudo htpasswd -c /etc/icingaweb2/.http-users icingaadmin
```

**Configuring the Web Server**

Add the following configuration to the **&lt;Directory&gt; Directive** in the **icingaweb.conf** web server
configuration file.

```
AuthType Basic
AuthName "Icinga Web 2"
AuthUserFile /etc/icingaweb2/.http-users
Require valid-user
```

Restart your web server to apply the changes.

## Active Directory or LDAP Authentication <a id="authentication-configuration-ad-or-ldap-authentication"></a>

If you want to authenticate against Active Directory or LDAP, you have to define a
[LDAP resource](04-Resources.md#resources-configuration-ldap) which will be referenced as data source for the
Active Directory or LDAP configuration method.

### LDAP <a id="authentication-configuration-ldap-authentication"></a>

| Directive                 | Description |
| ------------------------- | ----------- |
| **backend**               | `ldap` |
| **resource**              | The name of the LDAP resource defined in [resources.ini](04-Resources.md#resources). |
| **user_class**            | LDAP user class. |
| **user_name_attribute**   | LDAP attribute which contains the username. |
| **filter**                | LDAP search filter. |

**Example:**

```
[auth_ldap]
backend             = ldap
resource            = my_ldap
user_class          = inetOrgPerson
user_name_attribute = uid
filter              = "memberOf=cn=icinga_users,cn=groups,cn=accounts,dc=icinga,dc=org"
```

Note that in case the set *user_name_attribute* holds multiple values it is required that all of its
values are unique. Additionally, a user will be logged in using the exact user id used to authenticate
with Icinga Web 2 (e.g. an alias) no matter what the primary user id might actually be.

### Active Directory <a id="authentication-configuration-ad-authentication"></a>

| Directive     | Description |
| ------------- | ----------- |
| **backend**   | `msldap` |
| **resource**  | The name of the LDAP resource defined in [resources.ini](04-Resources.md#resources). |

**Example:**

```
[auth_ad]
backend  = msldap
resource = my_ad
```

## Database Authentication <a id="authentication-configuration-db-authentication"></a>

If you want to authenticate against a MySQL or a PostgreSQL database, you have to define a
[database resource](04-Resources.md#resources-configuration-database) which will be referenced as data source for the database
authentication method.

| Directive               | Description |
| ------------------------| ----------- |
| **backend**             | `db` |
| **resource**            | The name of the database resource defined in [resources.ini](04-Resources.md#resources). |

**Example:**

```
[auth_db]
backend  = db
resource = icingaweb-mysql
```

### Database Setup <a id="authentication-configuration-db-setup"></a>

For authenticating against a database, you have to import one of the following database schemas:

* **etc/schema/preferences.mysql.sql** (for **MySQL** database)
* **etc/schema/preferences.pgsql.sql** (for **PostgreSQL** databases)

After that you have to define the [database resource](04-Resources.md#resources-configuration-database).

**Manually Creating Users**

Icinga Web 2 uses the MD5 based BSD password algorithm. For generating a password hash, please use the following
command:

```
openssl passwd -1 password
```

> Note: The switch to `openssl passwd` is the **number one** (`-1`) for using the MD5 based BSD password algorithm.

Insert the user into the database using the generated password hash:

```
INSERT INTO icingaweb_user (name, active, password_hash) VALUES ('icingaadmin', 1, 'hash from openssl');
```

## Domain-aware Authentication <a id="domain-aware-auth"></a>

If there are multiple LDAP/AD authentication backends with distinct domains, you should make Icinga Web 2 aware of the
domains. This is possible since version 2.5 and can be done by configuring each LDAP/AD backend's domain. You can also
use the GUI for this purpose. This enables you to automatically discover a suitable value based on your LDAP server's
configuration. (AD: NetBIOS name, other LDAP: domain in DNS-notation)

**Example:**

```
[auth_icinga]
backend             = ldap
resource            = icinga_ldap
user_class          = inetOrgPerson
user_name_attribute = uid
filter              = "memberOf=cn=icinga_users,cn=groups,cn=accounts,dc=icinga,dc=com"
domain              = "icinga.com"

[auth_example]
backend  = msldap
resource = example_ad
domain   = EXAMPLE
```

If you configure the domains like above, the icinga.com user "jdoe" will have to log in as "jdoe@icinga.com" and the
EXAMPLE employee "rroe" will have to log in as "rroe@EXAMPLE". They could also log in as "EXAMPLE\\rroe", but this gets
converted to "rroe@EXAMPLE" as soon as the user logs in.

**Caution!**

Enabling domain-awareness or changing domains in existing setups requires migration of the usernames in the Icinga Web 2
configuration. Consult `icingacli --help migrate config users` for details.

### Default Domain <a id="default-auth-domain"></a>

For the sake of simplicity a default domain can be configured (in `config.ini`).

**Example:**

```
[authentication]
default_domain = "icinga.com"
```

If you configure the default domain like above, the user "jdoe@icinga.com" will be able to just type "jdoe" as username
while logging in.

### How it works <a id="domain-aware-auth-process"></a>

### Active Directory <a id="domain-aware-auth-ad"></a>

When the user "jdoe@ICINGA" logs in, Icinga Web 2 walks through all configured authentication backends until it finds
one which is responsible for that user -- e.g. an Active Directory backend with the domain "ICINGA". Then Icinga Web 2
asks that backend to authenticate the user with the sAMAccountName "jdoe".

### SQL Database <a id="domain-aware-auth-sqldb"></a>

When the user "jdoe@icinga.com" logs in, Icinga Web 2 walks through all configured authentication backends until it
finds one which is responsible for that user -- e.g. a MySQL backend (SQL database backends aren't domain-aware). Then
Icinga Web 2 asks that backend to authenticate the user with the username "jdoe@icinga.com".
