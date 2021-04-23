# Modules

## Installation

A module should be installed in one of the [configured module paths](03-Configuration.md#general-configuration).
The default path in most installations is `/usr/share/icingaweb2/modules`.

Each directory in there contains the files for a particular module. The directory's name has to be the one
that is provided by the module's documentation. If there is none provided, it is usually the name of the
module in all lowercase. Some modules may use a name prefixed with `icingaweb2-module-`. If this is the case,
the directory's name should be that, but without the prefix.
(e.g. `icingaweb2-module-map` turns into `/usr/share/icingaweb2/modules/map`)

> **Note:**
>
> Remember to ensure that your web-server can access those files. Though, read permission only.

Once a module's files are in place, it needs to be enabled first before it can be used. This can either be done in
the UI at `Configuration -> Modules` or by using the icingacli: `icingacli module enable map`

In order for other non-admin users to access the module's functionality, it is required to permit access first.
This is done by granting the permission `module/<module-name>`. (e.g. `module/map`)

### Module Specific Instructions

A module may require further installation steps. Whether these need to be performed before enabling the module,
should be provided by the module's documentation. In any case, don't forget to apply these as well, otherwise
the module will most likely not function correctly.

### Examples

There are sample installation instructions provided for your convenience:

**Sample Tarball installation**

```sh
MODULE_NAME="map"
MODULE_VERSION="v1.1.0"
MODULE_AUTHOR="nbuchwitz"
MODULES_PATH="/usr/share/icingaweb2/modules"
MODULE_PATH="${MODULES_PATH}/${MODULE_NAME}"
RELEASES="https://github.com/${MODULE_AUTHOR}/icingaweb2-module-${MODULE_NAME}/archive"
mkdir "$MODULE_PATH" \
&& wget -q $RELEASES/${MODULE_VERSION}.tar.gz -O - \
   | tar xfz - -C "$MODULE_PATH" --strip-components 1
icingacli module enable "${MODULE_NAME}"
```

**Sample GIT installation**

```sh
MODULE_NAME="map"
MODULE_VERSION="v1.1.0"
MODULE_AUTHOR="nbuchwitz"
REPO="https://github.com/${MODULE_AUTHOR}/icingaweb2-module-${MODULE_NAME}"
MODULES_PATH="/usr/share/icingaweb2/modules"
git clone ${REPO} "${MODULES_PATH}/${MODULE_NAME}" --branch "${MODULE_VERSION}"
icingacli module enable "${MODULE_NAME}"
```

## Configuration

A module may also require configuration. Most modules provide additional tabs at their configuration page.
This is accessible in the UI at `Configuration -> Modules`. If not, and something isn't working, check the
module's documentation again for hints.

If you need access to a module's configuration files directly, they should be in a subdirectory `modules`
of Icinga Web 2's configuration directory. That is usually `/etc/icingaweb2/modules`. Each directory in
there should be named the same as its installation path. (e.g. `/etc/icingaweb2/modules/map`)
