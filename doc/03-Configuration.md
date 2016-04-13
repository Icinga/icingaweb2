# <a id="configuration"></a> Configuration

## Overview

Apart from its web configuration capabilities, the local configuration is
stored in `/etc/icingaweb2` by default (depending on your config setup).

File/Directory                              | Description
---------------------------------------------------------
config.ini                                  | General configuration (logging, preferences)
[resources.ini](04-Ressources.md)           | Global resources (Icinga Web 2 database for preferences and authentication, Icinga IDO database)
roles.ini                                   | User specific roles (e.g. `administrators`) and permissions
[authentication.ini](05-Authentication.md)  | Authentication backends (e.g. database)
enabledModules                              | Contains symlinks to enabled modules
modules                                     | Directory for module specific configuration
