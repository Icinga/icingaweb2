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



