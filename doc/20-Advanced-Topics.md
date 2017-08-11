# Advanced Topics <a id="advanced-topics"></a>

This chapter contains advanced Icinga Web 2 topics such as source installation
or automation hints.

## Installing Icinga Web 2 from Source <a id="installing-from-source"></a>

Although the preferred way of installing Icinga Web 2 is to use packages, it is also possible to install Icinga Web 2
directly from source.

### Getting the Source <a id="getting-the-source"></a>

First of all, you need to download the sources.

Git clone:

```
cd /usr/share/
git clone https://github.com/Icinga/icingaweb2.git icingaweb2
```

Tarball download (latest [release](https://github.com/Icinga/icingaweb2/releases/latest)):

```
cd /usr/share
wget https://github.com/Icinga/icingaweb2/archive/v2.4.1.zip
unzip v2.4.1.zip
mv icingaweb2-2.4.1 icingaweb2
```

### Installing Requirements from Source <a id="installing-from-source-requirements"></a>

You will need to install certain dependencies depending on your setup listed [here](02-Installation.md#installing-requirements).

The following example installs Apache2 as web server, MySQL as RDBMS and uses the PHP adapter for MySQL.
Adopt the package requirements to your needs (e.g. adding ldap for authentication) and distribution.

Example for RHEL/CentOS/Fedora:

```
yum install httpd mysql-server
yum install php php-gd php-intl php-ZendFramework php-ZendFramework-Db-Adapter-Pdo-Mysql
```

The setup wizard will check the pre-requisites later on.


### Installing Icinga Web 2 <a id="installing-from-source-example"></a>

Choose a target directory and move Icinga Web 2 there.

```
mv icingaweb2 /usr/share/icingaweb2
```

### Configuring the Web Server <a id="configuring-web-server"></a>

Use `icingacli` to generate web server configuration for either Apache or nginx.

**Apache**:
```
./bin/icingacli setup config webserver apache --document-root /usr/share/icingaweb2/public
```

**nginx**:
```
./bin/icingacli setup config webserver nginx --document-root /usr/share/icingaweb2/public
```

Save the output as new file in your webserver's configuration directory.

Example for Apache on RHEL or CentOS:
```
./bin/icingacli setup config webserver apache --document-root /usr/share/icingaweb2/public > /etc/httpd/conf.d/icingaweb2.conf
```

Example for Apache on SUSE:
```
./bin/icingacli setup config webserver apache --document-root /usr/share/icingaweb2/public > /etc/apache2/conf.d/icingaweb2.conf
```

Example for Apache on Debian Jessie:
```
./bin/icingacli setup config webserver apache --document-root /usr/share/icingaweb2/public > /etc/apache2/conf-available/icingaweb2.conf
a2enconf icingaweb2
```

Example for Apache on Alpine Linux:
```
icingacli setup config webserver apache --document-root /usr/share/webapps/icingaweb2/public > /etc/apache2/conf.d/icingaweb2.conf
```
### Preparing Icinga Web 2 Setup <a id="preparing-web-setup-from-source"></a>

You can set up Icinga Web 2 quickly and easily with the Icinga Web 2 setup wizard which is available the first time
you visit Icinga Web 2 in your browser. Please follow the steps listed below for preparing the web setup.

Because both web and CLI must have access to configuration and logs, permissions will be managed using a special
system group. The web server user and CLI user have to be added to this system group.

Add the system group `icingaweb2` in the first place.

**Fedora, RHEL, CentOS, SLES and OpenSUSE**:
```
groupadd -r icingaweb2
```

**Debian and Ubuntu**:
```
addgroup --system icingaweb2
```

Add your web server's user to the system group `icingaweb2`
and restart the web server:

**Fedora, RHEL and CentOS**:
```
usermod -a -G icingaweb2 apache
service httpd restart
```

**SLES and OpenSUSE**:
```
usermod -A icingaweb2 wwwrun
service apache2 restart
```

**Debian and Ubuntu**:
```
usermod -a -G icingaweb2 www-data
service apache2 restart
```

**Alpine Linux**:
```
gpasswd -a apache icingaweb2
rc-service apache2 restart
```


Use `icingacli` to create the configuration directory which defaults to **/etc/icingaweb2**:
```
./bin/icingacli setup config directory
```


When using the web setup you are required to authenticate using a token. In order to generate a token use the
`icingacli`:
```
./bin/icingacli setup token create
```

In case you do not remember the token you can show it using the `icingacli`:
```
./bin/icingacli setup token show
```

### Icinga Web 2 Setup Wizard <a id="web-setup-from-source-wizard"></a>

Finally visit Icinga Web 2 in your browser to access the setup wizard and complete the installation:
`/icingaweb2/setup`.

Paste the previously generated token and follow the steps on-screen. Then you are done here.


### Icinga Web 2 Manual Setup <a id="web-setup-manual-from-source"></a>

If you have chosen not to run the setup wizard, you will need further knowledge
about

* manual creation of the Icinga Web 2 database `icingaweb2` including a default user (optional as authentication and session backend)
* additional configuration for the application
* additional configuration for the monitoring module (e.g. the IDO database and external command pipe from Icinga 2)

This comes in handy if you are planning to deploy Icinga Web 2 automatically using
Puppet, Ansible, Chef, etc.

> **Warning**
>
> Read the documentation on the respective linked configuration sections before
> deploying the configuration manually.
>
> If you are unsure about certain settings, use the [setup wizard](02-Installation.md#web-setup-wizard-from-source) once
> and then collect the generated configuration as well as sql dumps.

#### Icinga Web 2 Manual Database Setup <a id="web-setup-manual-from-source-database"></a>

Create the database and add a new user as shown below for MySQL/MariaDB:

```
sudo mysql -p

CREATE DATABASE icingaweb2;
GRANT SELECT, INSERT, UPDATE, DELETE, DROP, CREATE VIEW, INDEX, EXECUTE ON icingaweb2.* TO 'icingaweb2'@'localhost' IDENTIFIED BY 'icingaweb2';
quit

mysql -p icingaweb2 < /usr/share/doc/icingaweb2/schema/mysql.schema.sql
```


Then generate a new password hash as described in the [authentication docs](05-Authentication.md#authentication-configuration-db-setup)
and use it to insert a new user called `icingaadmin` into the database.

```
mysql -p icingaweb2

INSERT INTO icingaweb_user (name, active, password_hash) VALUES ('icingaadmin', 1, '$1$EzxLOFDr$giVx3bGhVm4lDUAw6srGX1');
quit
```

#### Icinga Web 2 Manual Configuration <a id="web-setup-manual-from-source-config"></a>


[resources.ini](04-Resources.md#resources) providing the details for the Icinga Web 2 and
Icinga 2 IDO database configuration. Example for MySQL:

```
vim /etc/icingaweb2/resources.ini

[icingaweb2]
type                = "db"
db                  = "mysql"
host                = "localhost"
port                = "3306"
dbname              = "icingaweb2"
username            = "icingaweb2"
password            = "icingaweb2"


[icinga2]
type                = "db"
db                  = "mysql"
host                = "localhost"
port                = "3306"
dbname              = "icinga"
username            = "icinga"
password            = "icinga"
```

[config.ini](03-Configuration.md#configuration) defining general application settings.

```
vim /etc/icingaweb2/config.ini

[logging]
log                 = "syslog"
level               = "ERROR"
application         = "icingaweb2"


[preferences]
type                = "db"
resource            = "icingaweb2"
```

[authentication.ini](05-Authentication.md#authentication) for e.g. using the previously created database.

```
vim /etc/icingaweb2/authentication.ini

[icingaweb2]
backend             = "db"
resource            = "icingaweb2"
```


[roles.ini](06-Security.md#security) granting the previously added `icingaadmin` user all permissions.

```
vim /etc/icingaweb2/roles.ini

[admins]
users               = "icingaadmin"
permissions         = "*"
```

#### Icinga Web 2 Manual Configuration Monitoring Module <a id="web-setup-manual-from-source-config-monitoring-module"></a>


**config.ini** defining additional security settings.

```
vim /etc/icingaweb2/modules/monitoring/config.ini

[security]
protected_customvars = "*pw*,*pass*,community"
```

**backends.ini** referencing the Icinga 2 DB IDO resource.

```
vim /etc/icingaweb2/modules/monitoring/backends.ini

[icinga2]
type                = "ido"
resource            = "icinga2"
```

**commandtransports.ini** defining the Icinga 2 API command transport.

```
vim /etc/icingaweb2/modules/monitoring/commandtransports.ini

[icinga2]
transport = "api"
host = "localhost"
port = "5665"
username = "api"
password = "api"
```

#### Icinga Web 2 Manual Setup Login <a id="web-setup-manual-from-source-login"></a>

Finally visit Icinga Web 2 in your browser to login as `icingaadmin` user: `/icingaweb2`.

## Automating the Installation of Icinga Web 2

If you are automating the installation of Icinga Web 2, you may want to skip the wizard and do things yourself.
These are the steps you'd need to take assuming you are using MySQL/MariaDB. If you are using PostgreSQL please adapt
accordingly. Note you need to have successfully completed the Icinga 2 installation, installed the Icinga Web 2 packages
and all the other steps described above first.

1. Install PHP dependencies: `php`, `php-intl`, `php-imagick`, `php-gd`, `php-mysql`, `php-curl`, `php-mbstring` used
by Icinga Web 2.
2. Set a timezone in `php.ini` configuration file.
3. Create a database for Icinga Web 2, i.e. `icingaweb2`.
4. Import the database schema: `mysql -D icingaweb2 < /usr/share/icingaweb2/etc/schema/mysql.schema.sql`.
5. Insert administrator user in the `icingaweb2` database:
`INSERT INTO icingaweb_user (name, active, password_hash) VALUES ('admin', 1, '<hash>')`, where `<hash>` is the output
of `openssl passwd -1 <password>`.
5. Make sure the `ido-mysql` and `api` features are enabled in Icinga 2: `icinga2 feature enable ido-mysql` and 
`icinga2 feature enable api`.
6. Generate Apache/nginx config. This command will print an apacahe config for you on stdout:
`icingacli setup config webserver apache`. Similarly for nginx. You need to place that configuration in the right place,
for example `/etc/apache2/sites-enabled/icingaweb2.conf`.
7. Add `www-data` user to `icingaweb2` group if not done already (`usermod -a -G icingaweb2 www-data`).
8. Create the Icinga Web 2 configuration in `/etc/icingaweb2`. The directory can be easily created with:
`icingacli setup config webserver`. This command ensures that the directory has the appropriate ownership and
permissions. If you want to create the directory manually, make sure to chown the group to `icingaweb2` and set the
access mode to `2770`.

The structure of the configurations looks like the following:

```
/etc/icingaweb2/
/etc/icingaweb2/authentication.ini
/etc/icingaweb2/modules
/etc/icingaweb2/modules/monitoring
/etc/icingaweb2/modules/monitoring/config.ini
/etc/icingaweb2/modules/monitoring/instances.ini
/etc/icingaweb2/modules/monitoring/backends.ini
/etc/icingaweb2/roles.ini
/etc/icingaweb2/config.ini
/etc/icingaweb2/enabledModules
/etc/icingaweb2/enabledModules/monitoring
/etc/icingaweb2/enabledModules/doc
/etc/icingaweb2/resources.ini
```

Have a look [here](20-Advanced-Topics.md#web-setup-manual-from-source-config) for the contents of the files.

