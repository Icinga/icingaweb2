# Configuration <a id="configuration"></a>

## Overview <a id="configuration-overview"></a>

Apart from its web configuration capabilities, the local configuration is
stored in `/etc/icingaweb2` by default (depending on your configuration setup).

File/Directory                                          | Description
------------------------------------------------------- | ---------------------------------
[config.ini](03-Configuration.md#configuration-general) | General configuration (global, logging, themes, etc.)
[resources.ini](04-Resources.md#resources)              | Global resources (Icinga Web 2 database for preferences and authentication, Icinga 2 IDO database)
[roles.ini](06-Security.md#security-roles)              | User specific roles (e.g. `administrators`) and permissions
[authentication.ini](05-Authentication.md)              | Authentication backends (e.g. database)
enabledModules                                          | Symlinks to enabled modules
modules                                                 | Directory for module specific configuration

Comments in .ini files follow the (de facto) standard in the form of a semicolon (`;`) at the beginning of a (each) line.

## General Configuration <a id="configuration-general"></a>

Navigate into **Configuration > Application > General **.

This configuration is stored in the `config.ini` file in `/etc/icingaweb2`.

### Global Configuration <a id="configuration-general-global"></a>


Option                   | Description
-------------------------|-----------------------------------------------
show\_stacktraces        | **Optional.** Whether to show debug stacktraces. Defaults to `0`.
module\_path             | **Optional.** Specifies the directories where modules can be installed. Multiple directories must be separated with colons.
config\_resource         | **Required.** Specify a defined [resource](04-Resources.md#resources-configuration-database) name.


Example for storing the user preferences in the database resource `icingaweb_db`:

```
[global]
show_stacktraces = "0"
config_resource = "icingaweb_db"
module_path = "/usr/share/icingaweb2/modules"
```

### Security Configuration <a id="configuration-general-security"></a>

| Option           | Description                                                                                                                           |
|------------------|---------------------------------------------------------------------------------------------------------------------------------------|
| use\_strict\_csp | **Optional.** Set this to `1` to enable strict [Content Security Policy](20-Advanced-Topics.md#advanced-topics-csp). Defaults to `0`. |

Example:

```
[security]
use_strict_csp = "1"
```

### Logging Configuration <a id="configuration-general-logging"></a>

Option                   | Description
-------------------------|-----------------------------------------------
log                      | **Optional.** Specifies the logging type. Can be set to `syslog`, `file`, `php` (web server's error log) or `none`.
level                    | **Optional.** Specifies the logging level. Can be set to `ERROR`, `WARNING`, `INFORMATION` or `DEBUG`.
file                     | **Optional.** Specifies the log file path if `log` is set to `file`.
application              | **Optional.** Specifies the application name if `log` is set to `syslog`.
facility                 | **Optional.** Specifies the syslog facility if `log` is set to `syslog`. Can be set to `user`, `local0` to `local7`. Defaults to `user`.

Example for more verbose debug logging into a file:

```
[logging]
log = "file"
level = "DEBUG"
file = "/usr/share/icingaweb2/log/icingaweb2.log"
```

### Theme Configuration <a id="configuration-general-theme"></a>

Option                   | Description
-------------------------|-----------------------------------------------
default                  | **Optional.** Choose the default theme. Can be set to `Icinga`, `high-contrast`, `Winter`, 'colorblind' or your own installed theme. Defaults to `Icinga`. Note that this setting is case-sensitive because it refers to the filename of the theme.
disabled                 | **Optional.** Set this to `1` if users should not be allowed to change their theme. Defaults to `0`.

Example:

```
[themes]
disabled = "1"
default = "high-contrast"
```
