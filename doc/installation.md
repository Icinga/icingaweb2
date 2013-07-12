
# Installation

## configure && make

### Basic installation

If you like to configurea and install icinga2-web from the command line or 
if you want to create packages, configure and make is the best choice for installation.


`
./configure && make install && make install-apache2-config
`
will install the application to the default target (/usr/local/icinga2-web). Also 
an apache configuration entry is added to your apache server, so you should restart
your web server according to your systems configuration.

### Installation directory

If you want to install the application to a different directory, use the --prefix flag in your 
configure call:
`
./configure --prefix=/my/target/directory
` 

### Authentication

By default, icinga2-web will be installed to authenticate againts its internal database,
but you can configure it to use ldap-authentication by adding the `--with-ldap-authentication` 
flag. You must provide the authentication details for your ldap server by using the --with-ldap-* flags.
To see a full list of the flags, call `./configure --help`

### Icinga backend

The default option for icinga2web is to configure all icinga backends with the default settings (for example
/usr/local/icinga/ as the icinga directory) but only enable statusdat. To use a different backend,
call `--with-icinga-backend=` and provide ido, livestatus or statusdat as an option. To further configure
your backend, take a look at the various options described in `./configure --help` 



Quick and Dirty
----------------

tdb.
