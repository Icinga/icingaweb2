# Auditing <a id="auditing"></a>

Auditing in Icinga Web 2 is possible with a separate [module](https://github.com/Icinga/icingaweb2-module-audit).

This module provides different logging facilities to store/record activities by Icinga Web 2 users.

Icinga Web 2 currently emits the following activities:

## Authentication

Activity | Additional Data
---------|----------------
login    | username
logout   | username
