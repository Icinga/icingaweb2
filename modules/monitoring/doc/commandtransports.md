# <a id="instances"></a> The instance.ini configuration file

## Abstract

The instance.ini defines how icingaweb accesses the command pipe of your icinga process in order to submit external
commands. Depending on the config path (default: /etc/icingaweb2) of your icingaweb installation you can find it
under ./modules/monitoring/instances.ini.

## Syntax

You can define multiple instances in the instances.ini, icingaweb will use the first one as the default instance.

Every instance starts with a section header containing the name of the instance, followed by the config directives for
this instance in the standard ini format used by icingaweb.

## Using a local icinga pipe

A local icinga instance can be easily setup and only requires the 'path' parameter:

    [icinga]
    path=/usr/local/icinga/var/rw/icinga.cmd

When sending commands to the icinga instance, icingaweb just opens the file found underneath 'path' and writes the external
command to it.

## Using ssh for accessing an icinga pipe

When providing at least a host directive to the instances.ini, SSH will be used for accessing the pipe. You must have
setup key authentication at the endpoint and allow your icingweb's user to access the machine without a password at this time:

    [icinga]
    path=/usr/local/icinga/var/rw/icinga.cmd ; the path on the remote machine where the icinga.cmd can be found
    host=my.remote.machine.com               ; the hostname or address of the remote machine
    port=22                                  ; the port to use (22 if none is given)
    user=jdoe                                ; the user to authenticate with

You can also make use of the ssh resource for accessing an icinga pipe with key-based authentication, which will give
you the possibility to define the location of the private key for a specific user, let's have a look:

    [icinga]
    path=/usr/local/icinga/var/rw/icinga.cmd ; the path on the remote machine where the icinga.cmd can be found
    host=my.remote.machine.com               ; the hostname or address of the remote machine
    port=22                                  ; the port to use (22 if none is given)
    resource=ssh                             ; the ssh resource which contains the username and the location of the private key

And the associated ssh resource:

    [ssh]
    type                = "ssh"
    user                = "ssh-user"
    private_key        = "/etc/icingaweb2/ssh/ssh-user"




