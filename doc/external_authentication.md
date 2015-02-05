# External Authentication

It is possible to utilize the authentication mechanism of the webserver instead
of the internal authentication of Icinga Web 2 to authenticate users. This might
be useful if you only have very few users and user management over **.htaccess**
is not sufficient or if you are required to use some other authentication
mechanism that is only available by utilizing the webserver.

Icinga Web 2 will entrust the complete authentication process to the
authentication provider of the webserver, if external authentication is used.
So it is very important that the webserver's authentication is configured
correctly as wrong configuration might lead to unauthorized access or a
malfunction in the login-process.

## Using External Authentication

External authentication in Icinga Web 2 requires the following preparations:

1. The external authentication must be set up properly to correctly
   authenticate users
2. Icinga Web 2 must be configured to use external authentication

### Preparing the External Authentication Provider

This step depends heavily on the used webserver and authentication mechanism you
want to use. It is not possible to cover all possibillities and you should
probably read the documentation for your webserver to get detailed instructions
on how to set up authentication properly.

In general you need to make sure that:

- All routes require authentication
- Only permitted users are allowed to authenticate

#### Example Configuration for Apache and HTTPDigestAuthentication

The following example will show how to enable external authentication in Apache
using *HTTP Digest Authentication*.

##### Creating users

To create users for digest authentication you can use the tool *htdigest*. In
this example **.icingawebdigest** is the name of the file containing the user
credentials.

This command creates a new file with the user *jdoe*. *htdigest* will prompt
you for a password. If you want to add more users to the file you need to omit
the *-c* parameter in all following commands to not to overwrite the file.

````
sudo htdigest -c /etc/icingaweb2/.icingawebdigest "Icinga Web 2" jdoe
````

##### Configuring the Webserver

The webserver should require authentication for all public Icinga Web 2 files.

````
<Directory "/usr/share/icingaweb2/public">
    AuthType digest
    AuthName "Icinga Web 2"
    AuthDigestProvider file
    AuthUserFile /etc/icingaweb2/.icingawebdigest
    Require valid-user
</Directory>
````

To get these changes to work, make sure to enable the module for
HTTPDigestAuthentication and restart the webserver.

### Preparing Icinga Web 2

Once external authentication is set up correctly you need to configure Icinga
Web 2. In case you already completed the setup wizard it is likely that you are
now finished.

To get Icinga Web 2 to use external authentication the file
**config/authentication.ini** is required. Just add the following section
called "autologin", or any name of your choice, and save your changes:

````
[autologin]
backend = external
````

Congratulations! You are now logged in when visiting Icinga Web 2.