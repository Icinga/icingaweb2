# <a id="monitoring-security"></a> Security

The monitoring module provides an additional set of restrictions and permissions
that can be used for access control. The following sections will list those
restrictions and permissions in detail:


## Permissions

The Icinga Web 2 monitoring module can send commands to the current Icinga2 instance
through the command pipe. A user needs specific permissions to be able to send those  
commands when using the monitoring module.


| Name                                        | Permits                                                                     |
| ------------------------------------------- | --------------------------------------------------------------------------- |
| monitoring/command/*                        | Allow all commands                                                          |
| monitoring/command/schedule-check           | Allow scheduling host and service checks'                                   |
| monitoring/command/acknowledge-problem      | Allow acknowledging host and service problems                               |
| monitoring/command/remove-acknowledgement   | Allow removing problem acknowledgements                                     |
| monitoring/command/comment/*                | Allow adding and deleting host and service comments                         |
| monitoring/command/comment/add              | Allow commenting on hosts and services                                      |
| monitoring/command/downtime/delete          | Allow deleting host and service downtimes'                                  |
| monitoring/command/process-check-result     | Allow processing host and service check results                             |
| monitoring/command/feature/instance         | Allow processing commands for toggling features on an instance-wide basis   |
| monitoring/command/feature/object           | Allow processing commands for toggling features on host and service objects |
| monitoring/command/send-custom-notification | Allow sending custom notifications for hosts and services                   |


## <a id="monitoring-security-restrictions"></a> Restrictions

The monitoring module allows filtering objects:


| Keys                       | Restricts                                     |
| ---------------------------|---------------------------------------------- |
| monitoring/filter/objects  | Applies a filter to all hosts and services    |


This filter will affect all hosts and services. Furthermore, it will also
affect all related objects, like notifications, downtimes and events. If a
service is hidden, all notifications, downtimes on that service will be hidden too.


### Filter Column Names

The following filter column names are available in filter expressions:


| Column                                                       |
| ------------------------------------------------------------ |
| instance_name                                                |
| host_name                                                    |
| hostgroup_name                                               |
| service_description                                          |
| servicegroup_name                                            |
| + all custom variables prefixed with `_host_` or `_service_` |
