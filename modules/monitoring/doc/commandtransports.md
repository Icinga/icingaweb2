# <a id="commandtransports"></a> External Command Transport Configuration

## Introduction

The `commandtransports.ini` defines how Icinga Web 2 transports commands to your Icinga instance in order to submit
external commands. By default, this file is located at `/etc/icingaweb2/modules/monitoring/commandtransports.ini`.

You can define multiple command transports in the `commandtransports.ini`. Every transport starts with a section header
containing its name, followed by the config directives for this transport in the standard INI-format.

Icinga Web 2 will try one transport after another to send a command until the command is successfully sent.
If [configured](#commandtransports-multiple-instances), Icinga Web 2 will take different instances into account.
The order in which Icinga Web 2 processes the configured transports is defined by the order of sections in
`commandtransports.ini`.

## Use the Icinga 2 API

If you're running Icinga 2 it's best to use the Icinga 2 API for transmitting external commands.

First, please make sure that your server running Icinga Web 2 has the `PHP cURL` extension installed and enabled.

Second, you have to enable the `api` feature on the Icinga 2 host where you want to send the commands to:

```
icinga2 feature enable api
```

Next, you have to create an ApiUser object for authenticating against the Icinga 2 API. This configuration also applies
to the host where you want to send the commands to. We recommend to create/edit the file
`/etc/icinga2/conf.d/api-users.conf`:

```
object ApiUser "web2" {
  password = "bea11beb7b810ea9ce6ea" // Change this!
  permissions = [ "actions/*", "objects/modify/hosts", "objects/modify/services", "objects/modify/icingaapplication" ]
}
```

The permissions `actions/*`, `objects/modify/hosts`, `objects/modify/services`, `objects/modify/icingaapplication` are
mandatory in order to submit all external commands from within Icinga Web 2.

**Restart Icinga 2** for the changes to take effect.

After that, you have to set up Icinga Web 2's `commandtransport.ini` to use the Icinga 2 API:

```
[icinga2]
transport = "api"
host = "127.0.0.1" // Icinga 2 host
port = "5665"
username = "web2"
password = "bea11beb7b810ea9ce6ea" // Change that!
```

## Use a Local Command Pipe

A local Icinga instance requires the following directives:

```
[icinga2]
transport   = local
path        = /var/run/icinga2/cmd/icinga2.cmd
```

When sending commands to the Icinga instance, Icinga Web 2 opens the file found
on the local filesystem underneath 'path' and writes the external command to it.

## Use SSH For a Remote Command Pipe

A command pipe on a remote host's filesystem can be accessed by configuring a
SSH based command transport and requires the following directives:

```
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
[icinga2]
transport   = remote
path        = /var/run/icinga2/cmd/icinga2.cmd
host        = example.tld
resource    = example.tld-icinga2
;port        = 22 ; Optional. The default is 22
```

The resource's configuration needs to be put into the resources.ini file:

```
[example.tld-icinga2]
type        = ssh
user        = icinga
private_key = /etc/icingaweb2/ssh/icinga
```

## <a id="commandtransports-multiple-instances"></a> Configure Transports for Different Icinga Instances

If there are multiple but different Icinga instances writing to your IDO, you can define which transport belongs to
which Icinga instance by providing the directive `instance`. This directive should contain the name of the Icinga
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
