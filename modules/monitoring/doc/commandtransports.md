# <a id="commandtransports"></a> The commandtransports.ini configuration file

## Abstract

The commandtransports.ini defines how Icinga Web 2 accesses the command pipe of
your Icinga instance in order to submit external commands. Depending on the
config path (default: /etc/icingaweb2) of your Icinga Web 2 installation you can
find it under ./modules/monitoring/commandtransports.ini.

## Syntax

You can define multiple command transports in the commandtransports.ini. Every
transport starts with a section header containing its name, followed by the
config directives for this transport in the standard INI-format.

Icinga Web 2 will try one transport after another to send a command, depending
on the respective Icinga instance, until the command is successfully sent. The
order in which Icinga Web 2 processes the configured transports is defined by
the order of sections in the commandtransports.ini.

## Using a local command pipe

A local Icinga instance requires the following directives:

````
[icinga2]
transport = local
path = /var/run/icinga2/cmd/icinga2.cmd
````

When sending commands to the Icinga instance, Icinga Web 2 opens the file found
on the local filesystem underneath 'path' and writes the external command to it.

## Using SSH for accessing a remote command pipe

A command pipe on a remote host's filesystem can be accessed by configuring a
SSH based command transport and requires the following directives:

````
[icinga2]
transport = remote
path = /var/run/icinga2/cmd/icinga2.cmd
host = example.tld
;port = 22                              ; Optional. The default is 22
user = icinga
````

To make this example work, you'll need to permit your web-server's user
public-key based access to the defined remote host so that Icinga Web 2 can
connect to it and login as the defined user.

You can also make use of a dedicated SSH resource to permit access for a
different user than the web-server's one. This way, you can provide a private
key file on the local filesystem that is used to access the remote host.

To accomplish this, a new resource is required that is defined in your
transport's configuration instead of a user:

````
[icinga2]
transport = remote
path = /var/run/icinga2/cmd/icinga2.cmd
host = example.tld
;port = 22                              ; Optional. The default is 22
resource = example.tld-icinga2
````

The resource's configuration needs to be put into the resources.ini file:

````
[example.tld-icinga2]
type = ssh
user = icinga
private_key = /etc/icingaweb2/ssh/icinga
````

## Configuring transports for different Icinga instances

If there are multiple but different Icinga instances writing to your IDO you can
define which transport belongs to which Icinga instance by providing the
directive 'instance'. This directive should contain the name of the Icinga
instance you want to assign to the transport:

````
[icinga1]
...
instance = icinga1

[icinga2]
...
instance = icinga2
````

Associating a transport to a specific Icinga instance causes this transport to
be used to send commands to the linked instance only. Transports without a
linked Icinga instance are utilized to send commands to all instances.