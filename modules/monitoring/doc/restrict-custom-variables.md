# Restrict Access to Custom Variables (WIP)

* Restriction name: monitoring/blacklist/properties
* Restriction value: Comma separated list of GLOB like filters 

Imagine the following host custom variable structure.

```
host.vars.
|-- cmdb_name
|-- cmdb_id
|-- cmdb_location
|-- wiki_id
|-- passwords.
|   |-- mysql_password
|   |-- ldap_password
|   `-- mongodb_password
|-- legacy.
|   |-- cmdb_name
|   |-- mysql_password
|   `-- wiki_id
`-- backup.
    `-- passwords.
        |-- mysql_password
        `-- ldap_password
```

`host.vars.cmdb_name`

Blacklists cmdb_name in the first level of the custom variable structure only.
`host.vars.legacy.cmdb_name` is not blacklisted.


`host.vars.cmdb_*`

All custom variables in the first level of the structure which begin with `cmdb_` become blacklisted.
Deeper custom variables are ignored. `host.vars.legacy.cmdb_name` is not blacklisted.

`host.vars.*id`

All custom variables in the first level of the structure which end with `id` become blacklisted.
Deeper custom variables are ignored. `host.vars.legacy.wiki_id` is not blacklisted.

`host.vars.*.mysql_password`

Matches all custom variables on the second level which are equal to `mysql_password`.

`host.vars.*.*password`

Matches all custom variables on the second level which end with `password`.

`host.vars.*.mysql_password,host.vars.*.ldap_password`

Matches all custorm variables on the second level which equal `mysql_password` or `ldap_password`.

`host.vars.**.*password`

Matches all custom variables on all levels which end with `password`.

Please note the two asterisks, `**`, here for crossing level boundaries. This syntax is used for matching the complete
custom variable structure.

If you want to restrict all custom variables that end with password for both hosts and services, you have to define
the following restriction.

`host.vars.**.*password,service.vars.**.*password`

## Escape Meta Characters

Use backslash to escape the meta characters

* *
* ,

`host.vars.\*fall`

Matches all custom variables in the first level which equal `*fall`.
