# <a id="security"></a> Security

In certain situations it is useful to allow someone access to IW2, but
to prevent him from doing certain actions or from seeing specific objects.
For example, it might be a good idea, to allow the configuration of IW2 to
a certain group of administrators to prevent misconfiguration or security-breaches.
Another important use case is the creation groups of users that can only see the
fraction of hosts and services of the monitoring environment they are in charge of.

The following chapter will describe how to do the security configuration of IW2
and how apply permissions and restrictions to users or groups of users.

## Basics

IW2 permissions are managed by defining **roles** that grant permissions or restrictions to **users** and **group**.
There are two general kinds of objects whose access can be managed in IW2:
**actions** and **objects**.

### Actions

Actions are all the things an IW2 user can do, like changing a certain configuration,
changing permissions or sending a command to the Icinga2-Core through the <a href="http://docs.icinga.org/icinga2/latest/doc/module/icinga2/toc#!/icinga2/latest/doc/module/icinga2/chapter/getting-started#setting-up-external-command-pipe">Command Pipe</a> in the monitoring module. All actions must be be **allowed explicitly** using permissions.

### Objects

There are all kinds of different objects in IW2, like Hosts, Services, Notifications and many more. **By default, a user can see all objects that are available in IW2**, but it is possible to define filters to restrict what each user can see.


### Users

Anyone that can **login** to IW2 is considered a user and can be referenced to by the
**login name**, independently of which mechanism was used to authenticate that user.
For example, there might be user called **jdoe**, which is authenticated
using Active Directory, and one user **icingaadmin**, that is authenticated using a MySQL-Database as backend, both users can be referenced to using their the login **icingaadmin** or **jdoe** in configuration.

IW2 users and groups are not configured by an IW2 configuration file, but provided by
an authentication backend. This means that how creating and deleting users works depends entirely on the backend. For extended information on setting up authentication backends, please read the chapter [authentication](authentication.md#authentication).

**ATTENTION !**

It is important to distinct between the "contacts" defined in the Icinga2-Core
configuration and the "users" that can access IW2. In this guide and for
handling security in IW2 in general only the latter ones are of importance.


### Groups

If there is a big amounts of users to manage, it would be tedious to specify each user
separately when referring to a bigger group of users at once. For this reasons, it
is possible to group users in groups.

Like with users, groups are identified solely by their **name**, and provided by backends called **group backends**. Like with users, it depends entirely on the used backend how groups are created and managed. For extended information on setting up group backends, please read the chapter [authentication](authentication.md#authentication).


TODO: example

## Roles

A role defines a set of **permissions** and **restrictions** in IW2, and assigns
those to users and user groups. For example, a role **admins** could define that certain
users have access to all configuration options, or another role **support**
could define that a list of users or groups is restricted to see only hosts and services
that match a specific query.

Roles can be assigned to groups and users. In the end, the actual permission of a certain user
will be determined by the sum of all roles that are assigned to the user, or to
groups the user is part of.


### Configuration

Roles can be changed either through the icingaweb2 interface, by navigation
to the page **Configuration > Authentication > Roles**, or through editing
configuration file:


        /etc/icingaweb2/roles.ini


### Example


    [winadmin]
    users = "jdoe, janedoe"  
    groups = "admin"
    permissions = "config/application/*, monitoring/commands/schedule-check"
    monitoring/hosts/filter = "host=*win*"
    monitoring/services/filter = "host=*win*"


This example creates a role called **winadmin**, that grants the permission **config/application/* ** and ** monitoring/commands/schedule-check ** and additonally only
allows the hosts and services that match the filter ** host=\*win* ** to be displayed. The users
**jdoe** and **janedoe** and all members of the group **admin** will be affected
by this role.

### Syntax

Each role is defined as a section, with the name of the role as section name. The following
attributes can be defined for each role:


| Key         | Value                                                            |
|-----------  |------------------------------------------------------------------|
| users       | A comma-separated list of user **login names** that are affected by this role   |
| groups      | A comma-separated list of **groups names** that are affected by this role  |
| permissions | A comma-separated list of permissions granted by this role       |
| module/monitoring/host | A filter expression applied to all hosts              |
| module/monitoring/serivce |  A fuilter expression applied to all services      |


### Permissions

Permissions can be used to allow users or groups certain **actions**. By default,
all actions are **prohibited** and must be allowed explicitly by a role for any user.

Each action in IW2 is denoted by a **namespaced key**, which is used to order and
group those actions. All actions that affect the configuration of IW2, are in a
namespace called **config**, while all configurations that affect authentication
are in the namespace **config/authentication**

**Wildcards** can be used to grant permission for all actions in a certain namespace.
The permission **config/* ** would grant permission to all configuration actions,
while just specifying a wildcard ** * ** would give permission for all actions.

When multiple roles assign permissions to the same user, either directly or indirectly
through a group, all permissions will be combined to get the users actual permission set.

###### Global permissions

| Keys | Permits |
|------|---------|
| *    |  Everything, including module-specific permissions |
| config/* | All configuration actions |
| config/application/* | Configuring IcingaWeb2 |
| config/application/general | General settings, like logging or preferences |
| config/application/resources | Change resources for retrieving data |
| config/application/userbackend | Change backends for retrieving available users |
| config/application/usergroupbackend | Change backends for retrieving available groups |
| config/authentication/* | Configure IcingaWeb2 authentication mechanisms |
| config/authentication/users/* | All user actions |
| config/authentication/users/show  | Display avilable users |
| config/authentication/users/add | Add a new user to the backend |
| config/authentication/users/edit | Edit existing user in the backend |
| config/authentication/users/remove | Remove existing user from the backend |
| config/authentication/groups/* | All group actions |
| config/authentication/groups/show | Display available groups |
| config/authentication/groups/add | Add a new group to the backend |
| config/authentication/groups/edit | Edit existing group in a backend |
| config/authentication/groups/remove | Remove existing group from the backend |
| config/authentication/roles/* | All role actions |
| config/authentication/roles/add | Add a new role |
| config/authentication/roles/show | Display available roles |
| config/authentication/roles/edit | Change an existing role |
| config/authentication/roles/remove | Remove an existing row |
| config/modules | Enable or disable modules and module settings |


###### Monitoring module permissions

| Keys | Permits |
|------|---------|
| monitoring/command/* | Allow all commands |
| monitoring/command/schedule-check | Allow scheduling host and service checks' |
| monitoring/command/acknowledge-problem | Allow acknowledging host and service problems |
| monitoring/command/remove-acknowledgement | Allow removing problem acknowledgements |
| monitoring/command/comment/* | Allow adding and deleting host and service comments |
| monitoring/command/comment/add | Allow commenting on hosts and services |
| monitoring/command/downtime/delete | Allow deleting host and service downtimes' |
| monitoring/command/process-check-result | Allow processing host and service check results |
| monitoring/command/feature/instance | Allow processing commands for toggling features on an instance-wide basis |
| monitoring/command/feature/object | Allow processing commands for toggling features on host and service objects |
| monitoring/command/send-custom-notification | Allow sending custom notifications for hosts and services |



### Restrictions

Restrictions can be used to define what a user or group can see, by specifying
a filter expression. By default, when no filter is defined, a user will be able
to see every object available. When a filter is specified for a certain object type
the user will only be able to see objects applied

The filter expression will be **implicitly** added as an **AND-Clause**
to each query. Any URL filter **?foo=bar&baz=bar** in IW2, would therefore implicitly
become ** ?(foo=bar&baz=bar)&($FILTER)**, depending on the users current restrictions.

When multiple roles assign restrictions to the same user, either directly or indirectly
through a group, all filters will be combined using an **AND-Clause**, resulting in the final
expression ** ?(foo=bar&baz=bar)&$ FILTER1 & $FILTER2 & $FILTER3** appended to each query.


##### Monitoring module filters

The monitoring module provides **hosts** and **services** as filterable objects:


| Keys                       | Restricts                                        |
|----------------------------|--------------------------------------------------|
| monitoring/hosts/filter    | Only display **hosts** that match this filter    |
| monitoring/services/filter | Only display **services** that match this filter |


Filters on hosts or services will automatically apply to all notifications, events
or downtimes that belong to those objects.


** ATTENTION ! **

Unlike with notifications or downtimes, a host filter will **not** automatically apply to all services on that host. If this behavior is desired, it is always necessary to add the
filter expression to both, **services and hosts**.


##### Filter Expressions

Any filter expression that is allowed in the filtered view, is also an allowed filter expression.
This means, that it is possible to define negations, wildcards, and even nested
filter expressions containing AND and OR-Clauses.

The following examples will show some examples for more complex filters:

###### Example 1: Negation

    [onlywin]
    groups = "windows-admins"
    monitoring/hosts/filter = "host=*win*"
    monitoring/services/filter = "host=*win*"

Will display only hosts and services whose hosts contains the **win**.

    [nowin]
    groups = "unix-admins"  
    monitoring/hosts/filter = "host!=*win*"
    monitoring/services/filter = "host!=*win*"

Will only match hosts and services whose host does **not** contain **win**

Also notice that if a user that is member of both groups, **windows-admins** and **unix-admins**,
he wont be able to see any hosts, as both filters will be applied and remove any object from the query.


###### Example 2: Hostgroups

    [only-unix]
    groups = "unix-admins"  
    monitoring/hosts/filter = "(hostgroup_name=bsd-servers|hostgroup_name=linux-servers)"
    monitoring/services/filter = "(hostgroup_name=bsd-servers|hostgroup_name=linux-servers)"


This role allows all members of the group unix-admins to only see hosts and services
that are part of the host-group linux-servers or the host-group bsd-servers.

## Modules

When creating IW2 modules, it might be necessary to define new actions that can be granted or revoked to certain users. One example for this, is the monitoring module, that is the heart of the monitoring functionality of IW2.while

## Troubleshooting

Q: I cannot see the menu for changing roles in Icinga Web 2

A: Your user lacks the permission to change the authentication configuration,
   add a new role in roles.ini that gives your current user
   the permission to access **config/authentication/* **
