# External Command Transport Configuration <a id="monitoring-module-commandtransports"></a>

## Configuration <a id="monitoring-module-commandtransports-configuration"></a>

Navigate into `Configuration` -> `Modules` -> `Monitoring` -> `Backends`.
You can create/edit command transports here.

The `commandtransports.ini` configuration file defines how Icinga Web 2
transports commands to your Icinga instance in order to submit
external commands. By default, this file is located at `/etc/icingaweb2/modules/monitoring/commandtransports.ini`.

You can define multiple command transports in the `commandtransports.ini` file. Every transport starts with a section header
containing its name, followed by the config directives for this transport in the standard INI-format.

Icinga Web 2 will try one transport after another to send a command until the command is successfully sent.
If [configured](05-Command-Transports.md#commandtransports-multiple-instances), Icinga Web 2 will take different instances into account.
The order in which Icinga Web 2 processes the configured transports is defined by the order of sections in
`commandtransports.ini`.

## Use the Icinga 2 API <a id="commandtransports-icinga2-api"></a>

If you're running Icinga 2 it's best to use the [Icinga 2 API](https://www.icinga.com/docs/icinga2/latest/doc/12-icinga2-api/)
for transmitting external commands.

### Icinga 2 Preparations <a id="commandtransports-icinga2-api-preparations"></a>

You have to run the `api` setup on the Icinga 2 host where you want to send the commands to:

```
icinga2 api setup
```

Next, you have to create an ApiUser object for authenticating against the Icinga 2 API. This configuration also applies
to the host where you want to send the commands to. We recommend to create/edit the file
`/etc/icinga2/conf.d/api-users.conf`:

```
object ApiUser "icingaweb2" {
  password = "bea11beb7b810ea9ce6ea" // Change this!
  permissions = [ "status/query", "actions/*", "objects/modify/*", "objects/query/*" ]
}
```

The permissions are mandatory in order to submit all external commands from within Icinga Web 2.

**Restart Icinga 2** for the changes to take effect.

```
systemctl restart icinga2
```

### Configuration in Icinga Web 2 <a id="commandtransports-icinga2-api-configuration"></a>

> **Note**
>
> Please make sure that your server running Icinga Web 2 has the `PHP cURL` extension installed and enabled.

The Icinga 2 API requires the following settings:

Option                   | Description
-------------------------|-----------------------------------------------
transport                | **Required.** The transport type. Must be set to `api`.
host                     | **Required.** The host address where the Icinga 2 API is listening on.
port                     | **Required.** The port where the Icinga 2 API is listening on. Defaults to `5665`.
username                 | **Required.** Basic auth username.
password                 | **Required.** Basic auth password.

Example:

```
# vim /etc/icingaweb2/modules/monitoring/commandtransports.ini

[icinga2]
transport = "api"
host = "127.0.0.1" ; Icinga 2 host
port = "5665"
username = "icingaweb2"
password = "bea11beb7b810ea9ce6ea" ; Change this!
```

## Use a Local Command Pipe <a id="commandtransports-local-command-pipe"></a>

A local Icinga instance requires the following settings:

Option                   | Description
-------------------------|-----------------------------------------------
transport                | **Required.** The transport type. Must be set to `local`.
path                     | **Required.** The absolute path to the local command pipe.

Example:

```
# vim /etc/icingaweb2/modules/monitoring/commandtransports.ini

[icinga2]
transport   = local
path        = /var/run/icinga2/cmd/icinga2.cmd
```

When commands are being sent to the Icinga instance, Icinga Web 2 opens the file found
on the local filesystem underneath `path` and writes the external command to it.

Please note that errors are not returned using this method. The Icinga 2 API sends
error feedback.

## Use SSH For a Remote Command Pipe <a id="commandtransports-ssh-remote-command-pipe"></a>

A command pipe on a remote host's filesystem can be accessed by configuring a
SSH based command transport and requires the following settings:

Option                   | Description
-------------------------|-----------------------------------------------
transport                | **Required.** The transport type. Must be set to `remote`.
path                     | **Required.** The path on the remote server to its local command pipe.
host                     | **Required.** The SSH host.
port                     | **Optional.** The SSH port. Defaults to `22`.
user                     | **Required.** The SSH auth user.
resource                 | **Optional.** The SSH [resource](../../../doc/04-Resources.md#resources-configuration-ssh)
instance                 | **Optional.** The Icinga instance name. Only required for multiple instances.

Example:

```
# vim /etc/icingaweb2/modules/monitoring/commandtransports.ini

[icinga2]
transport   = remote
path        = /var/run/icinga2/cmd/icinga2.cmd
host        = example.tld
user        = icinga
;port        = 22 ; Optional. The default is 22
```

To make this example work, you'll need to permit your web-server's user
public-key based access to the defined remote host so that Icinga Web 2 can
connect to it and login as the defined user.

You can also make use of a dedicated SSH resource to permit access for a
different user than the web-server's one. This way, you can provide a private
key file on the local filesystem that is used to access the remote host.

To accomplish this, a new resource is required that is defined in your
transport's configuration instead of a user:

```
# vim /etc/icingaweb2/modules/monitoring/commandtransports.ini

[icinga2]
transport   = remote
path        = /var/run/icinga2/cmd/icinga2.cmd
host        = example.tld
resource    = example.tld-icinga2
;port        = 22 ; Optional. The default is 22
```

The resource's configuration needs to be put into the resources.ini file:

```
# vim /etc/icingaweb2/resources.ini

[example.tld-icinga2]
type        = ssh
user        = icinga
private_key = /etc/icingaweb2/ssh/icinga
```

## Configure Transports for Different Icinga Instances <a id="commandtransports-multiple-instances"></a>

If there are multiple but different Icinga instances writing to your IDO database,
you can define which transport belongs to which Icinga instance by providing the
`instance` setting. This setting must specify the name of the Icinga
instance you want to assign to the transport:

```
[icinga1]
...
instance = icinga1

[icinga2]
...
instance = icinga2
```

Associating a transport to a specific Icinga instance causes this transport to be used to send commands to the linked
instance only. Transports without a linked Icinga instance are used to send commands to all instances.
