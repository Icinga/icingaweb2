# Resources

The configuration file *config/resources.ini* contains data sources that can be referenced
in other configurations. This allows you to manage all connections to databases at one central
place, avoiding the need to edit several different files, when the connection information of a resource change.

## Configuration

Each section represents a resource, with the section name being the identifier used to
reference this certain section. Depending on the resource type, each section contains different properties.
The property *type* defines the resource type and thus how the properties are going to be interpreted.
Currently only the resource type *db* is available.

### db

This resource type describes a SQL database. The property *db* defines the used database vendor, which
could be a value like *mysql* or *pgsql*. The other properties like *host*, *password*, *username* and
*dbname* are the connection information for the resource.


## Factory Implementations

This section contains documentation documentation for the Icinga2-Web developers that want to
use resources defined in the *resources.ini*. Each supported resource type should have an own
factory class, that can be used to comfortably create instances of classes that provide access
to the data of the resources.


### DbAdapterFactory

The DbAdapterFactory can be used to retrieve instances of Zend_Db_Adapter_Abstract for accessing
the data of the SQL database.

Lets assume for the following examples, that we have an *resources.ini* that looks like this:

    [resource1]
    type        =   "db"
    db          =   "mysql"
    dbname      =   "resource1"
    host        =   "host"
    username    =   "username1"
    password    =   "password1"

    [resource2]
    type        =   "db"
    db          =   "pgsql"
    dbname      =   "resource2"
    host        =   "host"
    username    =   "username2"
    password    =   "password2"

    [resource3]
    type        =   "other"
    foo         =   "foo"
    bar         =   "bar"


In the most simple use-case you can create an adapter by calling the
*getDbAdapter* function. The created adapter will be an instance of
Zend_Db_Adapter_Pdo_Mysql

    $adapter = DbAdapterFactory::getDbAdapter('resource1');


If you specify a resource that does not exist or has the wrong type,
the factory will throw an ConfigurationException. You can make sure
a resource exists and has the right type, by calling the function *resourceExists*:

    if (DbAdapterFactory::resourceExists('resource3')) {
        $adapter = DbAdapterFactory::getDbAdapter('resource3');
    } else {
        // This returned false, because resource3 has a different type than "db"
        echo 'resource does not exist, adapter could not be created...'
    }


You can retrieve a list of all available resources by calling *getResources*. You will
get an array of all resources that have the type 'db'.