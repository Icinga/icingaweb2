# Authentication <a id="authentication"></a>

You can authenticate against Active Directory, LDAP, a MySQL or a PostgreSQL database or delegate
authentication to the web server.

Authentication methods can be chained to set up fallback authentication methods
or if users are spread over multiple places.

## Configuration <a id="authentication-configuration"></a>

Navigate into **Configuration > Application > Authentication **.

Authentication methods are configured in the `/etc/icingaweb2/authentication.ini` file.

Each section in the authentication configuration represents a single authentication method.

The order of entries in the authentication configuration determines the order of the authentication methods.
If the current authentication method errors or if the current authentication method does not know the account being
authenticated, the next authentication method will be used.

## External Authentication <a id="authentication-configuration-external-authentication"></a>

Authentication to the web server can be delegated with the `autologin` section
which specifies an external backend.

Option                   | Description
-------------------------|-----------------------------------------------
backend                  | **Required.** Specifies the backend type. Must be set to `external`.
strip\_username\_regexp  | **Optional.** Regular expression to strip off specific user name parts.

Example:

```
# vim /etc/icingaweb2/authentication.ini

[autologin]
backend = external
```

If your web server is not configured for authentication though, the `autologin` section has no effect.

### Example Configuration for Apache and Basic Authentication <a id="authentication-configuration-external-authentication-example"></a>

The following example will show you how to enable external authentication in Apache
using basic authentication.

#### Create Basic Auth User <a id="authentication-configuration-external-authentication-example-user"></a>

You can use the tool `htpasswd` to generate basic authentication credentials. This example writes the
user credentials into the `.http-users` file.

The following command creates a new file which adds the user `icingaadmin`.
`htpasswd` will prompt you for a password.
If you want to add more users to the file you have to omit the `-c` switch to not overwrite the file.

```
sudo htpasswd -c /etc/icingaweb2/.http-users icingaadmin
```

#### Apache Configuration <a id="authentication-configuration-external-authentication-example-apache"></a>

Add the following configuration to the `&lt;Directory&gt;` directive in the `icingaweb2.conf` web server
configuration file.

```
AuthType Basic
AuthName "Icinga Web 2"
AuthUserFile /etc/icingaweb2/.http-users
Require valid-user
```

Restart your web server to apply the changes.

Example on CentOS 7:

```
systemctl restart httpd
```

## Active Directory or LDAP Authentication <a id="authentication-configuration-ad-or-ldap-authentication"></a>

If you want to authenticate against Active Directory or LDAP, you have to define an
[LDAP resource](04-Resources.md#resources-configuration-ldap).
This is referenced as data source for the Active Directory or LDAP configuration method.

### LDAP <a id="authentication-configuration-ldap-authentication"></a>

Option                   | Description
-------------------------|-----------------------------------------------
backend                  | **Required.** Specifies the backend type. Must be set to `ldap`.
resource                 | **Required.** The name of the LDAP resource defined in [resources.ini](04-Resources.md#resources).
user\_class              | **Optional.** LDAP user class. Defaults to `inetOrgPerson`.
user\_name\_attribute    | **Optional.** LDAP attribute which contains the username. Defaults to `uid`.
filter                   | **Optional.** LDAP search filter. Requires `user_class` and `user_name_attribute`.

> **Note for SELinux**
>
> If you run into problems connecting with LDAP and have SELinux enabled, take a look [here](90-SELinux.md#selinux-optional-booleans).

Example:

```
# vim /etc/icingaweb2/authentication.ini

[auth_ldap]
backend             = ldap
resource            = my_ldap
user_class          = inetOrgPerson
user_name_attribute = uid
filter              = "memberOf=cn=icinga_users,cn=groups,cn=accounts,dc=icinga,dc=org"
```

If `user_name_attribute` specifies multiple values all of them must be unique.
Please keep in mind that a user will be logged in with the exact user id used to authenticate
with Icinga Web 2 (e.g. an alias) ignoring the actual primary user id.

### Active Directory <a id="authentication-configuration-ad-authentication"></a>

Option                   | Description
-------------------------|-----------------------------------------------
backend                  | **Required.** Specifies the backend type. Must be set to `msldap`.
resource                 | **Required.** The name of the LDAP resource defined in [resources.ini](04-Resources.md#resources).
user\_class              | **Optional.** LDAP user class. Defaults to `user`.
user\_name\_attribute    | **Optional.** LDAP attribute which contains the username. Defaults to `sAMAccountName`.
filter                   | **Optional.** LDAP search filter. Requires `user_class` and `user_name_attribute`.

Example:

```
# vim /etc/icingaweb2/authentication.ini

[auth_ad]
backend  = msldap
resource = my_ad
```

## Database Authentication <a id="authentication-configuration-db-authentication"></a>

If you want to authenticate against a MySQL or a PostgreSQL database, you have to define a
[database resource](04-Resources.md#resources-configuration-database) which will be referenced as data source for the database
authentication method.

Option                   | Description
-------------------------|-----------------------------------------------
backend                  | **Required.** Specifies the backend type. Must be set to `db`.
resource                 | **Required.** The name of the database resource defined in [resources.ini](04-Resources.md#resources). |

Example:

```
# vim /etc/icingaweb2/authentication.ini

[auth_db]
backend  = db
resource = icingaweb-mysql
```

Please read [this chapter](20-Advanced-Topics.md#advanced-topics-authentication-tips-manual-user-database-auth)
in order to manually create users directly inside the database.


## Groups <a id="authentication-configuration-groups"></a>

Navigate into **Configuration > Application > Authentication **.

Group configuration is stored in the `/etc/icingaweb2/groups.ini` file.

### LDAP Groups <a id="authentication-configuration-groups-ldap"></a>

Option                   | Description
-------------------------|-----------------------------------------------
backend                  | **Required.** Specifies the backend type. Can be set to `ldap`, `msldap`.
resource                 | **Required.** The name of the LDAP resource defined in [resources.ini](04-Resources.md#resources).
domain                   | **Optional.** The domain the LDAP server is responsible for. See [Domain-aware Authentication](05-Authentication.md#domain-aware-auth).
user\_class              | **Optional.** LDAP user class. Defaults to `inetOrgPerson` with `msldap` and `user` with `ldap`.
user\_name\_attribute    | **Optional.** LDAP attribute which contains the username. Defaults to `sAMAccountName` with `msldap` and `uid` with `ldap`.
user\_base\_dn           | **Optional.** The path where users can be found on the LDAP server.
base_dn                  | **Optional.** LDAP base dn for groups. Leave empty to select all groups available using the specified resource.
group\_class             | **Optional.** LDAP group class. Defaults to `group`.
group\_member\_attribute | **Optional.** LDAP attribute where a group's members are stored. Defaults to `member`.
group\_name\_attribute   | **Optional.** LDAP attribute which contains the groupname. Defaults to `sAMAccountName` with `msldap` and `gid` with `ldap`.
group\_filter            | **Optional.** LDAP group search filter. Requires `group_class` and `group_name_attribute`.
nested\_group\_search    | **Optional.** Enable nested group search in Active Directory based on the user. Defaults to `0`. Only available with `backend` type `msldap`.

Example for Active Directory groups:

```
# vim /etc/icingaweb2/groups.ini

[active directory]
backend = "msldap"
resource = "auth_ad"
group_class = "group"
user_class = "user"
user_name_attribute = "userPrincipalName"
```

Example for Active Directory using the group backend resource `ad_company`.
It also references the defined user backend resource `ad_users_company`.

```
# vim /etc/icingaweb2/groups.ini

[ad_groups_company]
backend = "msldap"
resource = "ad_company"
user_backend = "ad_users_company"
nested_group_search = "1"
base_dn = "ou=Icinga,ou=Groups,dc=company,dc=com"
```

### Database Groups <a id="authentication-configuration-groups-database"></a>

Option                   | Description
-------------------------|-----------------------------------------------
backend                  | **Required.** Specifies the backend type. Must be set to `db`.
resource                 | **Required.** The name of the database resource defined in [resources.ini](04-Resources.md#resources).

Example:

```
# vim /etc/icingaweb2/groups.ini

[icingaweb2]
backend = "db"
resource = "icingaweb_db"
```


## Domain-aware Authentication <a id="domain-aware-auth"></a>

If there are multiple LDAP/AD authentication backends with distinct domains, you should make Icinga Web 2 aware of the
domains. This is possible since version 2.5 and can be done by configuring each LDAP/AD backend's domain. You can also
use the GUI for this purpose. This enables you to automatically discover a suitable value based on your LDAP server's
configuration. (AD: NetBIOS name, other LDAP: domain in DNS-notation)

**Example:**

```
# vim /etc/icingaweb2/authentication.ini

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

> **Caution!**
>
> Enabling domain-awareness or changing domains in existing setups requires migration of the usernames in the Icinga Web 2
> configuration. Consult `icingacli --help migrate config users` for details.

### Default Domain <a id="default-auth-domain"></a>

For the sake of simplicity a default domain can be configured (in `config.ini`).

**Example:**

```
# vim /etc/icingaweb2/config.ini

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
