# Security

Access control is a vital part of configuring Icinga Web 2 securely. It is important that not every user that has
access to Icinga Web 2 can perform any action or see any host and service. Allow only a small group of administrators
to change the Icinga Web 2 configuration to prevent mis-configuration and security breaches. Define different rules
to users and groups of users which should only see a part of the monitoring environment they're in charge of.

This chapter will describe how to configure such rules in Icinga Web 2 and how permissions, refusals, restrictions
and role inheritance work.

## Basics

Icinga Web 2 access control is done by defining **roles** that associate privileges with **users** and **groups**.
Privileges of a role consist of **permissions**, **refusals** and **restrictions**. A role can **inherit** privileges
from another role.

### Role Memberships

A role is tied to users or groups of users. Upon login, a user's roles are identified by the username or names of
groups the user is a member of.

> **Note**
>
> Since Icinga Web 2, users in the Icinga configuration and the web authentication are separated, to allow use of
> external authentication providers. This means that users and groups defined in the Icinga configuration are not
> available to Icinga Web 2. It uses its own authentication backend to fetch users and groups from,
> [which must be configured separately](05-Authentication.md#authentication).

### Privileges

Permissions are used to grant access. Whether this means that a user can see a certain area or perform a distinct
action is fully up to the permission in question. Without granting a permission, the user will lack access and won't
see the area or perform the action.

Refusals are used to deny access. So they're the exact opposite of permissions. Most permissions can be refused.
Refusing a permission will block the user's access no matter if another role grants the permission. Refusals
override permissions.

Restrictions are expressions that limit access. What this exactly means is up to how the restriction is being utilized.
Without any restriction, a user is supposed to see *everything*. A user that occupies multiple roles, which all define
a restriction of the same type, will see *more*.

## Roles

A user can occupy multiple roles. Permissions and restrictions stack up in this case, thus will grant *more* access.
Refusals still override permissions however. A refusal of one role negates the granted permission of any other role.

### Configuration

Roles can be changed either through the UI, by navigating to the page **Configuration > Authentication > Roles**,
or by editing the configuration file `/etc/icingaweb2/roles.ini`.

#### Example

The following shows a role definition from the configuration file mentioned above:

```
[winadmin]
users = "jdoe, janedoe"
groups = "admin"
permissions = "config/*, module/icingadb, icingadb/command/schedule-check"
refusals = "config/authentication"
icingadb/filter/objects = "host.name=*win*"
```

This describes a role with the name `winadmin`. The users `jdoe` and `janedoe` are members of it. Just like the
members of group `admin` are. Full configuration access is granted, except of the authentication configuration,
which is forbidden. It also grants access to the *icingadb* module which includes the ability to re-schedule
checks, but only on objects related to hosts whose name contain `win`.

#### Syntax

Each role is defined as a section, with the name of the role as section name. The following
options can be defined for each role in a default Icinga Web 2 installation:

Name                      | Description
--------------------------|-----------------------------------------------
parent                    | The name of the role from which to inherit privileges.
users                     | Comma-separated list of **usernames** that should occupy this role.
groups                    | Comma-separated list of **group names** whose users should occupy this role.
permissions               | Comma-separated list of **permissions** granted by this role.
refusals                  | Comma-separated list of **permissions** refused by this role.
unrestricted              | If set to `1`, owners of this role are not restricted in any way (Default: `0`)
icingadb/filter/objects   | **Filter expression** that restricts the access to icingadb objects.

### Administrative Roles

Roles that have the wildcard `*` as permission, have full access and don't need any further permissions. However,
they are still affected by refusals.

Unrestricted roles are supposed to allow users to access data without being limited to a subset of it. Once a user
occupies an unrestricted role, restrictions of the same and any other role are ignored.

### Inheritance

A role can inherit privileges from another role. Privileges are then combined the same way as if a user occupies
all roles in the inheritance path. Or to rephrase that, each role shares its members with all of its parents.

## Permissions

Each permission in Icinga Web 2 is denoted by a **namespaced key**, which is used to group permissions. All permissions
that affect the configuration of Icinga Web 2, are in a namespace called **config**, while all configuration options
that affect modules are covered by the permission `config/modules`.

**Wildcards** can be used to grant all permissions in a certain namespace. The permission `config/*` grants access to
all configuration options. Just specifying a wildcard `*` will grant all permissions.

Access to modules is restricted to users who have the related module permission granted. Icinga Web 2 provides
a module permission in the format `module/<moduleName>` for each installed module.

### General Permissions

Name                         | Permits
-----------------------------|-----------------------------------------------
\*                           | allow everything, including module-specific permissions
application/announcements    | allow to manage announcements
application/log              | allow to view the application log
config/\*                    | allow full config access
config/access-control/\*     | allow to fully manage access control
config/access-control/groups | allow to manage groups
config/access-control/roles  | allow to manage roles
config/access-control/users  | allow to manage user accounts
config/general               | allow to adjust the general configuration
config/modules               | allow to enable/disable and configure modules
config/navigation            | allow to view and adjust shared navigation items
config/resources             | allow to manage resources
user/\*                      | allow all account related functionalities
user/application/stacktraces | allow to adjust in the preferences whether to show stacktraces
user/password-change         | allow password changes in the account preferences
user/share/navigation        | allow to share navigation items
module/`<moduleName>`        | allow access to module `<moduleName>` (e.g. `module/icingadb`)

## Restrictions

Restrictions can be used to define what a user can see by specifying an expression that applies to a defined set of
data. By default, when no restrictions are defined, a user will be able to see the entire data that is available.

The syntax of the expression used to define a particular restriction varies. This can be a comma-separated list of
terms, or a full-blown filter. For more details on particular restrictions, check the table below or the module's
documentation providing the restriction.

### General Restrictions

Name                      | Applies to
--------------------------|------------------------------------------------------------------------------------------
application/share/users   | which users a user can share navigation items with (comma-separated list of usernames)
application/share/groups  | which groups a user can share navigation items with (comma-separated list of group names)

### Username placeholder

It is possible to reference the local username (without the domain part) of the user in restrictions. To accomplish
this, put the macro `$user.local_name$` in the restriction where you want it to appear.

This can come in handy if you have e.g. an attribute on hosts or services defining which user is responsible for it:
`host.vars.deputy=$user.local_name$|service.vars.deputy=$user.local_name$`
