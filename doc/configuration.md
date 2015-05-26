# <a id="configuration"></a> Configuration

## Overview

Apart from its web configuration capabilities, the local configuration is
stored in `/etc/icingaweb2` by default (depending on your config setup).

  Location                      | File	                | Description
  ------------------------------|-----------------------|---------------------------
  .     			| config.ini	        | General configuration (logging, preferences)
  .				| resources.ini         | Global resources (Icinga Web 2 database for preferences and authentication, icinga ido database)
  .		    		| roles.ini	        | User specific roles (e.g. `administrators`) and permissions
  .		    		| [authentication.ini](authentication.md)    | Authentication backends (e.g. database)
  enabledModules    		| Symlink	        | Contains symlinks to enabled modules from `/usr/share/icingaweb2/modules/*`. Defaults to [monitoring](modules/monitoring/doc/configuration.md) and `doc`.
  modules	    		| Directory	        | Module specific configuration
