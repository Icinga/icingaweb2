# Resources

The configuration file *config/resources.ini* contains data sources that can be referenced
in other configurations. This allows you to manage all connections to databases at one central
place, avoiding the need to edit several different files, when the connection information of a resource change.

## Configuration

Each section represents a resource, with the section name being the identifier used to
reference this certain section. Depending on the resource type, each section contains different properties.
The property *type* defines the resource type and thus how the properties are going to be interpreted.
The available resource types are 'db', 'statusdat', 'livestatus' and 'ldap' and are
described in detail in the following sections:

### db

This resource type describes a SQL database on an SQL server. Databases can contain users and groups
to handle authentication and permissions, or monitoring data using IDO.

- *db*:         defines the used database vendor, which could be a value like *mysql* or *pgsql*.
- *host*:       The hostname that is used to connect to the database.
- *port*:       The port that is used to connect to the database.
- *username*:   The user name that is used to authenticate.
- *password*:   The password of the user given in *username*.
- *dbname*:     The name of the database that contains the resources data.

### ldap

The resource is a tree in a ldap domain. This resource type is usually used to fetch users and groups
to handle authentication and permissions.

- *hostname*:   The hostname that is used to connect to the ldap server.
- *port*:       The port that is used to connect to the ldap server.
- *root_dn*:    The root object of the tree. This is usually an organizational unit like
                "ou=people, dc=icinga, dc=org".
- *bind_dn*:    The user on the LDAP server that will be used to access it. Usually something
                like "cn=admin, cn=config".
- *bind_pw*:    The password of the user given in *bind_dn*.


### livestatus

A resource that points to a livestatus socket. This resource type contains monitoring data.

- *socket*:     The livestatus socket. Can be either be a path to a domain socket (like
                "/usr/local/icinga-mysql/var/rw/live") or to a TCP socket like
                (tcp://<domain>:<port>)

### statusdat

A resource that points to statusdat files. This resource type contains monitoring data.

- *status_file*: The path to the *status.dat* file, like "/usr/local/icinga-mysql/var/status.dat"
- *object_file*: The path to *objects.cache*, like "/usr/local/icinga-mysql/var/objects.cache"


## Factory Implementations

This section contains documentation documentation for the Icinga2-Web developers that want to
use resources defined in the *resources.ini*. Each supported resource type should have an own
factory class, that can be used to comfortably create instances of classes that provide access
to the data of the resources.


### ResourceFactory

The ResourceFactory can be used to retrieve objects to access resources. Lets assume
for the following examples, that we have an *resources.ini* that looks like this:

    [statusdat]
    type                = statusdat
    status_file         = /usr/local/icinga-mysql/var/status.dat
    object_file         = /usr/local/icinga-mysql/var/objects.cache

    [ldap_authentication]
    type                = "ldap"
    hostname            = "localhost"
    port                = "389"
    root_dn             = "ou=people, dc=icinga, dc=org"
    bind_dn             = "cn=admin, cn=config"
    bind_pw             = "admin"


Here is an example of how to retrieve the resource 'statusdat' from the factory.

    $resource = ResourceFactory::createResource(
        ResourceFactory::getResourceConfig('statusdat')
    );

If you specify a resource that does not exist or has the wrong type,
the factory will throw an ConfigurationException.


You can also retrieve a list of all available resources by calling *getResourceConfigs*.

    $resourceConfigs = ResourceFactory::getResourceConfigs();
