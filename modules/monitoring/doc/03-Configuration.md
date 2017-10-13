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

