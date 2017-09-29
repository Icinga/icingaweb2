# Backends <a id="monitoring-module-backends"></a>

The configuration file `backends.ini` contains information about data sources which are
used to fetch monitoring objects presented to the user.

The required [resources](../../../doc/04-Resources.md#resources-configuration-database) must be globally defined beforehand.

## Configuration <a id="monitoring-module-backends-configuration"></a>

Navigate into `Configuration` -> `Modules` -> `Monitoring` -> `Backends`.
You can select a specified global resource here, and also update its details.

Each section in `backends.ini` references a resource. By default you should only have one backend enabled.

### IDO Backend <a id="monitoring-module-backends-ido"></a>

Option                   | Description
-------------------------|-----------------------------------------------
type                     | **Required.** Specify the backend type. Must be set to `ido`.
resource                 | **Required.** Specify a defined [resource](../../../doc/04-Resources.md#resources-configuration-database) name which provides details about the IDO database resource.


Example for using the database resource `icinga2_ido_mysql`:

```
[icinga2_ido_mysql]
type = "ido"
resource = "icinga2_ido_mysql"
```

