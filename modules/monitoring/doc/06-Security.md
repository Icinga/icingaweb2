# Security <a id="monitoring-module-security"></a>

The monitoring module provides an additional set of restrictions and permissions
that can be used for access control. The following sections will list those
restrictions and permissions in detail:


## Permissions <a id="monitoring-module-security-permissions"></a>

The monitoring module allows to send commands to an Icinga 2 instance.
A user needs specific permissions to be able to send those commands
when using the monitoring module.


Name                                        | Permits
--------------------------------------------|-----------------------------------------------
monitoring/command/\*                       | Allow all commands.
monitoring/command/schedule-check           | Allow scheduling host and service checks.
monitoring/command/acknowledge-problem      | Allow acknowledging host and service problems.
monitoring/command/remove-acknowledgement   | Allow removing problem acknowledgements.
monitoring/command/comment/\*               | Allow adding and deleting host and service comments.
monitoring/command/comment/add              | Allow commenting on hosts and services.
monitoring/command/downtime/delete          | Allow deleting host and service downtimes.
monitoring/command/process-check-result     | Allow processing host and service check results.
monitoring/command/feature/instance         | Allow processing commands for toggling features on an instance-wide basis.
monitoring/command/feature/object           | Allow processing commands for toggling features on host and service objects.
monitoring/command/send-custom-notification | Allow sending custom notifications for hosts and services.


## Restrictions <a id="monitoring-module-security-restrictions"></a>

The monitoring module allows filtering objects:


Keys                                        | Restricts
--------------------------------------------|-----------------------------------------------
monitoring/filter/objects                   | Applies a filter to all hosts and services.


This filter will affect all hosts and services. Furthermore, it will also
affect all related objects, like notifications, downtimes and events. If a
service is hidden, all notifications, downtimes on that service will be hidden too.


### Filter Column Names <a id="monitoring-module-security-restrictions-filter-column-names"></a>

The following filter column names are available in filter expressions:


Column                                                     | Description
-----------------------------------------------------------|-----------------------------------------------
instance\_name                                             | Filter on an Icinga 2 instance.
host\_name                                                 | Filter on host object names.
hostgroup\_name                                            | Filter on hostgroup object names.
service\_description                                       | Filter on service object names.
servicegroup\_name                                         | Filter on servicegroup object names.
all custom variables prefixed with `_host_` or `_service_` | Filter on specified custom variables.
