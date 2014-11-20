# <a id="authentication"></a> Authentication

**Choosing the Authentication Method**

With Icinga Web 2 you can authenticate against Active Directory, LDAP, a MySQL or PostgreSQL database or delegate
authentication to the web server. Authentication methods can be chained to set up fallback authentication methods
or if users are spread over multiple places.

## Configuration

Authentication methods are configured in the INI file **config/authentication.ini**.

Each section in the authentication configuration represents a single authentication method.

The order of entries in the authentication configuration determines the order of the authentication methods.
If the current authentication method errors or the current authentication method does not know the account being
authenticated, the next authentication method will be used.

## External Authentication

For delegating authentication to the web server simply add `autologin` to your authentication configuration:

````
[autologin]
backend = autologin
````

If your web server is not configured for authentication though the `autologin` section has no effect.

## Active Directory or LDAP Authentication

If you want to authenticate against Active Directory or LDAP, you have to define a
[LDAP resource](#resources-configuration-ldap) first which will be referenced as data source for the Active Directory
or LDAP configuration method.

### LDAP

Directive               | Description
------------------------|------------
**backend**             | `ldap`
**resource**            | The name of the LDAP resource defined in [resources.ini](resources).
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

### Active Directory

Directive               | Description
------------------------|------------
**backend**             | `ad`
**resource**            | The name of the LDAP resource defined in [resources.ini](resources).

**Example:**

```
[auth_ad]
backend  = ad
resource = my_ad
```

## Database Authentication

If you want to authenticate against a MySQL or PostgreSQL database, you have to define a
[database resource](#resources-configuration-database) first which will be referenced as data source for the database
authentication method.

Directive               | Description
------------------------|------------
**backend**             | `db`
**resource**            | The name of the database resource defined in [resources.ini](resources).

**Example:**

```
[auth_ad]
backend  = ad
resource = my_db
```

**Manually Creating Users**

````
openssl passwd -1 "password"

INSERT INTO icingaweb_user (name, active, password_hash) VALUES ('icingaadmin', 1, 'hash from openssl');
````
