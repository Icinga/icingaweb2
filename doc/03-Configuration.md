# Configuration <a id="configuration"></a>

## Overview <a id="configuration-overview"></a>

Apart from its web configuration capabilities, the local configuration is
stored in `/etc/icingaweb2` by default (depending on your config setup).

| File/Directory                                    | Description/Purpose |
| ------------------------------------------------- | ------------------- |
| **config.ini**                                    | general configuration (logging, preferences, etc.) |
| [**resources.ini**](04-Resources.md)              | global resources (Icinga Web 2 database for preferences and authentication, Icinga IDO database) |
| **roles.ini**                                     | user specific roles (e.g. `administrators`) and permissions |
| [**authentication.ini**](05-Authentication.md)    | authentication backends (e.g. database) |
| **enabledModules**                                | contains symlinks to enabled modules |
| **modules**                                       | directory for module specific configuration |
