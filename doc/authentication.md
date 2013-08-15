# Authentication

The authentication manager can use different backend types like LDAP or Databases as data sources. During
the application bootstrap the different available resources are checked for availability and
the resource with the highest priority will be used for authentication. This behaviour is useful for setting
up fallback accounts, that are available when the regular authentication backend is not available.

## Configuration

The internal authentication is configured in *config/authentication.ini*.

Each section listed in this configuration represents a single backend
that can be used to authenticate users or groups.

The order of entries in this configuration is used to determine the fallback
priority in case of an error. If the resource referenced in the first entry (the one at the top if the file)
is not reachable, the next lower entry will be used for authentication.
Please be aware that this behaviour is not valid for the authentication itself.
The authentication will only be done against the one available resource with the highest
priority. When an account is only present in a backend with lower priority, it will not
be able to authenticate when a backend with higher priority is active that does not contain
this account.

### Backend

The value of the configuration key *backend* will determine which UserBackend class to
load. To use the internal backend you need to specifiy the value "Db"
which will cause the class "DbUserBackend" to be loaded.

Currently these types of backends are allowed:
    * ldap
    * db

#### db

The authentication source is a SQL database and points to a resource defined in *resources.ini*, which
contains all the connection information. Every entry should therefore contain a property *resource*
with the name of the assigned resource. For a more detailed description about how to set up resources,
please read the chapter *Resources*.

The authentication currently supports the databases MySQL and PostgreSQL.

#### ldap

The authentication source is an ldap server. The connection information should be directly present
in the *authentication.ini*, like described in the example configuration.


### target

The value of the configuration key *target* defines the type of authentication the described backend provides.
The allowed values are *user* for a backend that provides user authentication or *group* for group authentication.


## Technical description

If an ldap-backend is used, the standard ldap bind will be executed and all user credentials will be managed
directly by the ldap server.

In case of an SQL-backend, the backend will store the salted hash of the password in the column "password" and the salt in the column "salt".
When a password is checked, the hash is calculated with the function hash_hmac("sha256",salt,password) and compared
to the stored value.
