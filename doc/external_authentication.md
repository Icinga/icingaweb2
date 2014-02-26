# Externel Authentication

It is possible to use the authentication mechanism of the webserver, 
instead of using the internal authentication-manager to
authenticate users. This might be useful if you only have very few users, and
user management over *.htaccess* is sufficient, or if you must use some other
authentication mechanism that is only available through your webserver.

When external authentication is used, Icingaweb will entrust the 
complete authentication process to the external authentication provider (the webserver):
The provider should take care of authenticating the user and declining
all requests with invalid or missing credentials. When the authentication 
was succesful, it should provide the authenticated users name to its php-module
and Icingaweb will assume that the user is authorized to access the page.
Because of this it is very important that the webservers authentication is
configured correctly, as wrong configuration could lead to unauthorized
access to the site, or a broken login-process. 


## Use External Authentication

Using external authentication in Icingaweb requires two steps to work:

1. The external authentication must be set up correctly to always
   authenticate the users.
2. Icingaweb must be configured to use the external authentication.


### Prepare the External Authentication Provider

This step depends heavily on the used webserver and authentication 
mechanism you want to use. It is not possible to cover all possibillities 
and you should probably read the documentation for your webserver for
detailed instructions on how to set up authentication properly.

In general, you need to make sure that:

	- All routes require authentication
	- Only permitted users are allowed to authenticate


#### Example Configuration for Apache and HTTPDigestAuthentication

The following example will show how to enable external authentication in Apache using
*HTTP Digest Authentication*.

##### Create users

To create users for a digest authentication we can use the tool *htdigest*.
We choose *.icingawebdigest* as a name for the created file, containing
the user credentials.

This command will create a new file with the user *jdoe*. *htdigest* 
will prompt you for your password, after it has been executed. If you 
want to add more users to the file you need to ommit the *-c* parameter 
in all further commands to avoInid the file to be overwritten.


		sudo htdigest -c /etc/httpd/conf.d/.icingawebdigest "Icingaweb 2" jdoe


##### Set up authentication

The webserver should require authentication for all public icingaweb files.


		<Directory "/var/www/html/icingaweb">
			AuthType digest
			AuthName "Icingaweb 2"
			AuthDigestProvider file
			AuthUserFile /etc/httpd/conf.d/.icingawebdigest
			Require valid-user
		</Directory>		

		
### Prepare Icingaweb

When the external authentication is set up correctly, we need
to configure IcingaWeb to use it as an authentication source. The
configuration key *authenticationMode* in the section *global* defines
if the authentication should be handled internally or externally. Since
we want to delegate the authentication to the Webserver we choose 
"external" as the new value:


		[global]
		; ...
		authenticationMode = "external"
		; ...
		
