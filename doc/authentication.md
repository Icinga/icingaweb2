# <a id="authentication"></a> Authentication

**Choosing the Authentication Method**

With Icinga Web 2 you can authenticate against Active Directory, LDAP, a MySQL or a PostgreSQL database or delegate
authentication to the web server.

Authentication methods can be chained to set up fallback authentication methods
or if users are spread over multiple places.

## <a id="authentication-configuration"></a> Configuration

Authentication methods are configured in the INI file **config/authentication.ini**.

Each section in the authentication configuration represents a single authentication method.

The order of entries in the authentication configuration determines the order of the authentication methods.
If the current authentication method errors or if the current authentication method does not know the account being
authenticated, the next authentication method will be used.

### <a id="authentication-configuration-external-authentication"></a> External Authentication

For delegating authentication to the web server simply add `autologin` to your authentication configuration:

````
[autologin]
backend = external
````

If your web server is not configured for authentication though the `autologin` section has no effect.

### <a id="authentication-configuration-ad-or-ldap-authentication"></a> Active Directory or LDAP Authentication

If you want to authenticate against Active Directory or LDAP, you have to define a
[LDAP resource](#resources-configuration-ldap) which will be referenced as data source for the Active Directory
or LDAP configuration method.

#### <a id="authentication-configuration-ldap-authentication"></a> LDAP

Directive               | Description
------------------------|------------
**backend**             | `ldap`
**resource**            | The name of the LDAP resource defined in [resources.ini](#resources).
**user_class**          | LDAP user class.
**user_name_attribute** | LDAP attribute which contains the username.

**Example:**

```
[auth_ldap]
backend             = ldap
resource            = my_ldap
user_class          = inetOrgPerson
user_name_attribute = uid
```

Note that in case the set *user_name_attribute* holds multiple values it is required that all of its
values are unique. Additionally, a user will be logged in using the exact user id used to authenticate
with Icinga Web 2 (e.g. an alias) no matter what the primary user id might actually be.

#### <a id="authentication-configuration-ad-authentication"></a> Active Directory

Directive               | Description
------------------------|------------
**backend**             | `ad`
**resource**            | The name of the LDAP resource defined in [resources.ini](#resources).

**Example:**

```
[auth_ad]
backend  = ad
resource = my_ad
```

### <a id="authentication-configuration-db-authentication"></a> Database Authentication

If you want to authenticate against a MySQL or a PostgreSQL database, you have to define a
[database resource](#resources-configuration-database) which will be referenced as data source for the database
authentication method.

Directive               | Description
------------------------|------------
**backend**             | `db`
**resource**            | The name of the database resource defined in [resources.ini](#resources).

**Example:**

```
[auth_ad]
backend  = ad
resource = icingaweb-mysql
```

#### <a id="authentication-configuration-db-setup"></a> Database Setup

For authenticating against a database, you have to import one of the following database schemas:

* **etc/schema/preferences.mysql.sql** (for **MySQL** database)
* **etc/schema/preferences.pgsql.sql** (for **PostgreSQL** databases)

After that you have to define the [database resource](#resources-configuration-database).

**Manually Creating Users**

Icinga Web 2 uses the MD5 based BSD password algorithm. For generating a password hash, please use the following
command:

````
openssl passwd -1 password
````

> Note: The switch to `openssl passwd` is the **number one** (`-1`) for using the MD5 based BSD password algorithm.

Insert the user into the database using the generated password hash:

````
INSERT INTO icingaweb_user (name, active, password_hash) VALUES ('icingaadmin', 1, 'hash from openssl');
````
