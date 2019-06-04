# Security <a id="security"></a>

Access control is a vital part of configuring Icinga Web 2 in a secure way.
It is important that not every user that has access to Icinga Web 2 is able
to do any action or to see any host and service. For example, it is useful to allow
only a small group of administrators to change the Icinga Web 2 configuration,
to prevent misconfiguration or security breaches. Another important use case is
creating groups of users which can only see the fraction of the monitoring
environment they are in charge of.

This chapter will describe how to do the security configuration of Icinga Web 2
and how to apply permissions and restrictions to users or groups of users.

## Basics <a id="security-basics"></a>

Icinga Web 2 access control is done by defining **roles** that associate permissions
and restrictions with **users** and **groups**. There are two general kinds of
things to which access can be managed: actions and objects.


### <a id="security-basics-actions"></a>Actions

Actions are all the things an Icinga Web 2 user can do, like changing a certain configuration,
changing permissions or sending a command to an Icinga 2 instance.
All actions must be be **allowed explicitly** using permissions.

A permission is a simple list of identifiers of actions a user is
allowed to do. Permissions are described in greater detail in the
section [Permissions](06-Security.md#permissions).

### Objects <a id="security-basics-objects"></a>

There are all kinds of different objects in Icinga Web 2: Hosts, Services, Notifications, Downtimes and Events.

By default, a user can **see everything**, but it is possible to **explicitly restrict** what each user can see using restrictions.

Restrictions are complex filter queries that describe what objects should be displayed to a user. Restrictions are described
in greater detail in the section [Restrictions](06-Security.md#restrictions).

### <a id="security-basics-users"></a>Users

Anyone who can **login** to Icinga Web 2 is considered a user and can be referenced to by the
**user name** used during login.
For example, there might be user called **jdoe** authenticated
using Active Directory, and a user **icingaadmin** that is authenticated using a MySQL-Database as backend.
In the configuration, both can be referenced to by using their user names **icingaadmin** or **jdoe**.

Icinga Web 2 users and groups are not configured by a configuration file, but provided by
an **authentication backend**. For extended information on setting up authentication backends and managing users, please read the chapter [Authentication](05-Authentication.md#authentication).


> **Note**
>
> Since Icinga Web 2, users in the Icinga configuration and the web authentication are separated, to allow
> use of external authentication providers. This means that users and groups defined in the Icinga configuration are not available to Icinga Web 2. Instead it uses its own authentication
> backend to fetch users and groups from, which must be configured separately.

#### <a id="security-basics-users-managing"></a>Managing Users

When using a [Database
as authentication backend](05-Authentication.md#authentication-configuration-db-authentication), it is possible to create, add and delete users directly in the frontend. This configuration
can be found at **Configuration > Authentication > Users **.

### <a id="security-basics-groups"></a>Groups

If there is a big amount of users to manage, it would be tedious to specify each user
separately when regularly referring to the same group of users. Because of that, it is possible to group users.
A user can be member of multiple groups and will inherit all permissions and restrictions.

Like users, groups are identified solely by their **name** that is provided by
 a **group backend**. For extended information on setting up group backends,
 please read the chapter [Authentication](05-Authentication.md#authentication).


#### <a id="security-basics-groups-managing"></a>Managing Groups

When using a [Database as an authentication backend](05-Authentication.md#authentication-configuration-db-authentication),
it is possible to manage groups and group memberships directly in the frontend. This configuration
can be found at **Configuration > Authentication > User Groups **.

## <a id="security-roles"></a>Roles

A role defines a set of **permissions** and **restrictions** and assigns
those to **users** and **groups**. For example, a role **admins** could define that certain
users have access to all configuration options, or another role **support**
could define that a list of users or groups is restricted to see only hosts and services
that match a specific query.

The actual permission of a certain user will be determined by merging the permissions
and restrictions of the user itself and all the groups the user is member of. Permissions can
be simply added up, while restrictions follow a slighty more complex pattern, that is described
in the section [Stacking Filters](06-Security.md#stacking-filters).

### <a id="security-roles-configuration"></a>Configuration

Roles can be changed either through the icingaweb2 interface, by navigation
to the page **Configuration > Authentication > Roles**, or through editing the
configuration file:

```
vim /etc/icingaweb2/roles.ini
```

#### <a id="security-roles-configuration-example"></a>Introducing Example

To get you a quick start, here is an example of what a role definition could look like:

```
[winadmin]
users = "jdoe, janedoe"
groups = "admin"
permissions = "config/*, monitoring/commands/schedule-check"
monitoring/filter/objects = "host_name=*win*"
```


This example creates a role called **winadmin**, that grants all permissions in `config/*` and `monitoring/commands/schedule-check` and additionally only
allows the hosts and services that match the filter `host_name=*win*` to be displayed. The users
**jdoe** and **janedoe** and all members of the group **admin** will be affected
by this role.


#### Syntax <a id="syntax"></a>

Each role is defined as a section, with the name of the role as section name. The following
attributes can be defined for each role in a default Icinga Web 2 installation:


Name                      | Description
--------------------------|-----------------------------------------------
users                     | Comma-separated list of user **user names** that are affected by this role.
groups                    | Comma-separated list of **group names** that are affected by this role.
permissions               | Comma-separated list of **permissions** granted by this role.
monitoring/filter/objects | **Filter expression** that restricts the access to services and hosts.



## Permissions <a id="permissions"></a>

Permissions can be used to allow users or groups certain **actions**. By default,
all actions are **prohibited** and must be allowed explicitly by a role for any user.

Each action in Icinga Web 2 is denoted by a **namespaced key**, which is used to order and
group those actions. All actions that affect the configuration of Icinga Web 2, are in a
namespace called **config**, while all configurations that affect modules
are in the namespace `config/modules`

**Wildcards** can be used to grant permission for all actions in a certain namespace.
The permission `config/*` would grant permission to all configuration actions,
while just specifying a wildcard `*` would give permission for all actions.

Access to modules is restricted to users who have the related module permission granted. Icinga Web 2 provides
a module permission in the format `module/<moduleName>` for each installed module.

When multiple roles assign permissions to the same user (either directly or indirectly
through a group) all permissions are added together to get the users actual permission set.

### Global Permissions <a id="permissions-global"></a>

Name                      | Permits
--------------------------|-----------------------------------------------
\*                        | allow everything, including module-specific permissions
config/\*                 | allow all configuration actions
config/modules            | allow enabling or disabling modules
module/&lt;moduleName&gt; | allow access to module &lt;moduleName&gt;


### Monitoring Module Permissions <a id="permissions-module"></a>

The built-in monitoring module defines an additional set of permissions, that
is described in detail in the monitoring module documentation.


## Restrictions <a id="restrictions"></a>

Restrictions can be used to define what a user or group can see by specifying
a filter expression that applies to a defined set of data. By default, when no
restrictions are defined, a user will be able to see every information that is available.

A restrictions is always specified for a certain **filter directive**, that defines what
data the filter is applied to. The **filter directive** is a simple identifier, that was
defined in an Icinga Web 2 module. The only filter directive that is available
in a default installation, is the `monitoring/filter/objects` directive, defined by the monitoring module,
that can be used to apply filter to hosts and services. This directive was previously
mentioned in the section [Syntax](06-Security.md#syntax).

### <a id="restrictions-filter"></a>Filter Expressions

Filters operate on columns. A complete list of all available filter columns on hosts and services can be found in
the monitoring module documentation.

Any filter expression that is allowed in the filtered view, is also an allowed filter expression.
This means, that it is possible to define negations, wildcards, and even nested
filter expressions containing AND and OR-Clauses.

The filter expression will be **implicitly** added as an **AND-Clause** to each query on
the filtered data. The following shows the filter expression `host_name=*win*` being applied on `monitoring/filter/objects`.


Regular filter query:

    AND-- service_problem = 1
     |
     +--- service_handled = 0


With our restriction applied, any user affected by this restrictions will see the
results of this query instead:


    AND-- host_name = *win*
     |
     +--AND-- service_problem = 1
         |
         +--- service_handled = 0

#### Username placeholder <a id="username-placeholder"></a>

The string `$user:local_name$` is replaced by the local username (without the domain part) of the logged on user while evaluating restrictions.
This can come in handy if you have some kind of attribute on host or service level defining which user is responsible for a certain host or service.

**Example**

```
monitoring/filter/objects = (_responsible=$user:local_name$|_deputy=$user:local_name$)
```

#### Stacking Filters <a id="stacking-filters"></a>

When multiple roles assign restrictions to the same user, either directly or indirectly
through a group, all filters will be combined using an **OR-Clause**, resulting in the final
expression:


       AND-- OR-- $FILTER1
        |     |
        |     +-- $FILTER2
        |     |
        |     +-- $FILTER3
        |
        +--AND-- service_problem = 1
            |
            +--- service_handled = 0


As a result, a user is be able to see hosts that are matched by **ANY** of
the filter expressions. The following examples will show the usefulness of this behavior:

#### <a id="restrictions-filter-example1"></a>Example 1: Negation

```
[winadmin]
groups = "windows-admins"
monitoring/filter/objects = "host_name=*win*"
```

Will display only hosts and services whose host name contains  **win**.

```
[webadmin]
groups = "web-admins"
monitoring/filter/objects = "host_name!=*win*"
```

Will only match hosts and services whose host name does **not** contain **win**

Notice that because of the behavior of two stacking filters, a user that is member of **windows-admins** and **web-admins**, will now be able to see both, Windows and non-Windows hosts and services.

#### <a id="restrictions-filter-example2"></a>Example 2: Hostgroups

```
[unix-server]
groups = "unix-admins"
monitoring/filter/objects = "(hostgroup_name=bsd-servers|hostgroup_name=linux-servers)"
```

This role allows all members of the group unix-admins to see hosts and services
that are part of the host-group linux-servers or the host-group bsd-servers.
