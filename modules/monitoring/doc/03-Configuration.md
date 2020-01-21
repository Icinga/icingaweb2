# Monitoring Module Configuration <a id="monitoring-module-configuration"></a>

## Overview <a id="monitoring-module-configuration-overview"></a>

The module specific configuration is stored in `/etc/icingaweb2/modules/monitoring`.

File/Directory                                                        | Description
----------------------------------------------------------------------|---------------------------------
config.ini                                                            | Security settings (e.g. protected custom vars) for the `monitoring` module |
[backends.ini](04-Backends.md#monitoring-module-backends)             | Data backend (e.g. the IDO database [resource](../../../doc/04-Resources.md#resources-configuration-database) name).
[commandtransports.ini](05-Command-Transports.md)                     | Command transports for specific Icinga instances


## General Configuration <a id="monitoring-module-configuration-general"></a>

Navigate into `Configuration` -> `Modules` -> `Monitoring`. This allows
you to see the provided [permissions and restrictions](06-Security.md#monitoring-security)
by this module.

### Default Settings <a id="monitoring-module-configuration-settings"></a>

Option                        | Description
------------------------------|-----------------------------------------------
acknowledge_expire            | **Optional.** Check "Use Expire Time" in Acknowledgement dialog by default. Defaults to **0 (false)**.
acknowledge_expire_time       | **Optional.** Set default value for "Expire Time" in Acknowledgement dialog, its calculated as now + this setting. Format is a [PHP Dateinterval](http://www.php.net/manual/en/dateinterval.construct.php). Defaults to **1 hour (PT1H)**.
acknowledge_notify            | **Optional.** Check "Send Notification" in Acknowledgement dialog by default. Defaults to **1 (true)**.
acknowledge_persistent        | **Optional.** Check "Persistent Comment" in Acknowledgement dialog by default. Defaults to **0 (false)**.
acknowledge_sticky            | **Optional.** Check "Sticky Acknowledgement" in Acknowledgement dialog by default. Defaults to **0 (false)**.
custom_notification_forced    | **Optional.** Check "Forced" in Custom Notification dialog by default. Defaults to **0 (false)**.
hostcheck_all_services        | **Optional.** Check "All Services" in Schedule Host Check dialog by default. Defaults to **0 (false)**.
hostdowntime_all_services     | **Optional.** Check "All Services" in Schedule Host Downtime dialog by default. Defaults to **0 (false)**.

Example for having acknowledgements with 2 hours expire time by default.

```
# vim /etc/icingaweb2/modules/monitoring/config.ini

[settings]
acknowledge_expire = 1
acknowledge_expire_time = PT2H

```

### Security Configuration <a id="monitoring-module-configuration-security"></a>

Option                   | Description
-------------------------|-----------------------------------------------
protected\_customvars    | **Optional.** Comma separated list of string patterns for custom variables which should be excluded from user's view.


Example for custom variable names which match `*pw*` or `*pass*` or `community`.

```
# vim /etc/icingaweb2/modules/monitoring/config.ini

[security]
protected_customvars = "*pw*,*pass*,community"
```

