# <a id="monitoring-configuration"></a> Monitoring Module Configuration

## Overview

Apart from its web configuration capabilities, the local configuration is
stored in `/etc/icingaweb2` by default (depending on your config setup).

  Location                      | File	                | Description
  ------------------------------|-----------------------|---------------------------
  modules/monitoring		| Directory		| `monitoring` module specific configuration
  modules/monitoring		| config.ini		| Security settings (e.g. protected custom vars) for the `monitoring` module
  modules/monitoring		| backends.ini		| Backend type and resources (e.g. Icinga IDO DB)
  modules/monitoring		| [instances.ini](instances.md#instances) | Instances and their transport (e.g. local external command pipe)



