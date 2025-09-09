# Advanced Topics <a id="advanced-topics"></a>

This chapter provides details for advanced Icinga Web 2 topics.

* [Global URL parameters](20-Advanced-Topics.md#global-url-parameters)
* [VirtualHost configuration](20-Advanced-Topics.md#virtualhost-configuration)
* [Content Security Policy (CSP)](20-Advanced-Topics.md#advanced-topics-csp)
* [Advanced Authentication Tips](20-Advanced-Topics.md#advanced-topics-authentication-tips)
* [Source installation](20-Advanced-Topics.md#installing-from-source)
* [Automated setup](20-Advanced-Topics.md#web-setup-automation)
* [Kiosk Mode Configuration](20-Advanced-Topics.md#kiosk-mode)
* [Customizing the Landing Page](20-Advanced-Topics.md#landing-page)

## Global URL Parameters <a id="global-url-parameters"></a>

Parameters starting with `_` are for development purposes only.

Parameter         | Value         | Description
------------------|---------------|--------------------------------
showFullscreen    | -             | Hides the left menu and optimizes the layout for full screen resolution.
showCompact       | -             | Provides a compact view. Hides the title and upper menu. This is helpful to embed a dashboard item into an external iframe.
format            | json/csv/sql  | Selected views can be exported as JSON or CSV. This also is available in the upper menu. You can also export the SQL queries for manual analysis.
\_dev             | 0/1           | Whether the server should return compressed or full JS/CSS files. This helps debugging browser console errors.



Examples for `showFullscreen`:

http://localhost/icingaweb2/dashboard?showFullscreen
http://localhost/icingaweb2/monitoring/list/services?service_problem=1&sort=service_severity&showFullscreen

Examples for `showCompact`:

http://localhost/icingaweb2/dashboard?showCompact&showFullscreen
http://localhost/icingaweb2/monitoring/list/services?service_problem=1&sort=service_severity&showCompact

Examples for `format`:

http://localhost/icingaweb2/monitoring/list/services?format=json
http://localhost/icingaweb2/monitoring/list/services?service_problem=1&sort=service_severity&dir=desc&format=csv


## VirtualHost Configuration <a id="virtualhost-configuration"></a>

This describes how to run Icinga Web 2 on your FQDN's `/` entry point without
any redirect to `/icingaweb2`.

### VirtualHost Configuration for Apache <a id="virtualhost-configuration-apache"></a>

Use the setup CLI commands to generate the default Apache configuration which serves
Icinga Web 2 underneath `/icingaweb2`.

The next steps are to create the VirtualHost configuration:

* Copy the `<Directory "/usr/share/icingaweb2/public">` into the main VHost configuration. Don't forget to correct the indent.
* Set the `DocumentRoot` variable to look into `/usr/share/icingaweb2/public`
* Modify the `RewriteBase` variable to use `/` instead of `/icingaweb2`

Example on RHEL/CentOS:

```
vim /etc/httpd/conf.d/web.icinga.com.conf

<VirtualHost *:80>
  ServerName web.icinga.com

  ## Vhost docroot
  # modified for Icinga Web 2
  DocumentRoot "/usr/share/icingaweb2/public"

  ## Rewrite rules
  RewriteEngine On

  <Directory "/usr/share/icingaweb2/public">
      Options SymLinksIfOwnerMatch
      AllowOverride None

      <IfModule mod_authz_core.c>
          # Apache 2.4
          <RequireAll>
              Require all granted
          </RequireAll>
      </IfModule>

      <IfModule !mod_authz_core.c>
          # Apache 2.2
          Order allow,deny
          Allow from all
      </IfModule>

      SetEnv ICINGAWEB_CONFIGDIR "/etc/icingaweb2"

      EnableSendfile Off

      <IfModule mod_rewrite.c>
          RewriteEngine on
          # modified base
          RewriteBase /
          RewriteCond %{REQUEST_FILENAME} -s [OR]
          RewriteCond %{REQUEST_FILENAME} -l [OR]
          RewriteCond %{REQUEST_FILENAME} -d
          RewriteRule ^.*$ - [NC,L]
          RewriteRule ^.*$ index.php [NC,L]
      </IfModule>

      <IfModule !mod_rewrite.c>
          DirectoryIndex error_norewrite.html
          ErrorDocument 404 /error_norewrite.html
      </IfModule>
  </Directory>
</VirtualHost>
```

Reload Apache and open the FQDN in your web browser.

```
systemctl reload httpd
```

### Content Security Policy (CSP) <a id="advanced-topics-csp"></a>

Elevate your security standards to an even higher level by enabling the [Content Security Policy (CSP)](https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP) for Icinga Web.
Enabling strict CSP can prevent your Icinga Web environment from becoming a potential target of [Cross-Site Scripting (XSS)](https://developer.mozilla.org/en-US/docs/Glossary/Cross-site_scripting)
and data injection attacks. After enabling this feature Icinga Web defines all the required CSP headers. Subsequently,
only content coming from Icinga Web's own origin is accepted, inline JS is prohibited, and inline CSS is accepted only
if it contains the nonce set in the response header. 

We decided against enabling this by default as we cannot guarantee that all the modules out there will function correctly.
Therefore, you have to manually enable this policy explicitly and accept the risks that this might break some of
the Icinga Web modules. Icinga Web and all it's components listed below, on the other hand, fully support strict CSP. If
that's not the case, please submit an issue on GitHub in the respective repositories.

To enable the strict content security policy navigate to **Configuration > Application** and toggle "Enable strict content security policy",
or set the `use_strict_csp` in the `config.ini`.

```
vim /etc/icingaweb2/config.ini

[security]
use_strict_csp = "1"
```

Here is a list of all Icinga Web components that are capable of strict CSP.

| Name                              | CSP supported since                                                                       |
|-----------------------------------|-------------------------------------------------------------------------------------------|
| Icinga DB Web                     | [v1.1.0](https://github.com/Icinga/icingadb-web/releases/tag/v1.1.0)                      |
| Icinga Reporting                  | [v1.0.0](https://github.com/Icinga/icingaweb2-module-reporting/releases/tag/v1.0.0)       |
| Icinga IDO Reports                | [v0.10.1](https://github.com/Icinga/icingaweb2-module-idoreports/releases/tag/v0.10.1)    |
| Icinga Cube                       | [v1.3.2](https://github.com/Icinga/icingaweb2-module-cube/releases/tag/v1.3.2)            |
| Icinga Director                   | [v1.11.1](https://github.com/Icinga/icingaweb2-module-director/releases/tag/v1.11.1)      |
| Icinga Business Process Modeling  | [v2.5.0](https://github.com/Icinga/icingaweb2-module-businessprocess/releases/tag/v2.5.0) |
| Icinga Certificate Monitoring     | [v1.3.0](https://github.com/Icinga/icingaweb2-module-x509/releases/tag/v1.3.0)            |
| Icinga PDF Export                 | [v0.10.2](https://github.com/Icinga/icingaweb2-module-pdfexport/releases/tag/v0.10.2)     |
| Icinga Web Jira Integration       | [v1.3.2](https://github.com/Icinga/icingaweb2-module-jira/releases/tag/v1.3.2)            |
| Icinga Web Graphite Integration   | [v1.3.0](https://github.com/Icinga/icingaweb2-module-graphite/releases/tag/v1.2.4)        |
| Icinga Web GenericTTS Integration | [v2.1.0](https://github.com/Icinga/icingaweb2-module-generictts/releases/tag/v2.1.0)      |
| Icinga Web Nagvis Integration     | [v1.2.0](https://github.com/Icinga/icingaweb2-module-nagvis/releases/tag/v1.2.0)          |
| Icinga Web AWS Integration        | [v1.1.0](https://github.com/Icinga/icingaweb2-module-aws/releases/tag/v1.1.0)             |
| Icinga Web vSphere Integration    | [v1.8.0](https://github.com/Icinga/icingaweb2-module-vspheredb/releases/tag/v1.8.0)       |


## Advanced Authentication Tips <a id="advanced-topics-authentication-tips"></a>

### Manual User Creation for Database Authentication Backend <a id="advanced-topics-authentication-tips-manual-user-database-auth"></a>

Icinga Web 2 v2.5+ uses the [native password hash algorithm](https://php.net/manual/en/faq.passwords.php)
provided by PHP 5.6+.

In order to generate a password, run the following command with the PHP CLI >= 5.6:

```
php -r 'echo password_hash("yourtopsecretpassword", PASSWORD_DEFAULT);'
```

Please note that the hashed output changes each time. This is expected.

Insert the user into the database using the generated password hash.

```
INSERT INTO icingaweb_user (name, active, password_hash) VALUES ('icingaadmin', 1, '$2y$10$bEKU6.1bRYjE7wxktqfeO.IGV9pYAkDBeXEbjMFSNs26lKTI0JQ1q');
```

#### Puppet <a id="advanced-topics-authentication-tips-manual-user-database-auth-puppet"></a>

Please do note that the `$` character needs to be escaped with a leading backslash in your
Puppet manifests.

Example from [puppet-icingaweb2](https://github.com/Icinga/puppet-icingaweb2):

```
        exec { 'create default user':
          command     => "mysql -h '${db_host}' -P '${db_port}' -u '${db_username}' -p'${db_password}' '${db_name}' -Ns -e 'INSERT INTO icingaweb_user (name, active, password_hash) VALUES (\"icingaadmin\", 1, \"\$2y\$10\$QnXfBjl1RE6TqJcY85ZKJuP9AvAV3ont9QihMTFQ/D/vHmAWaz.lG\")'",
          refreshonly => true,
        }
```


## Icinga Web 2 Manual Setup <a id="web-setup-manual-from-source"></a>

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
> If you are unsure about certain settings, use the setup wizard as described in the
> [installation instructions](02-Installation.md) once and then collect the generated
> configuration as well as sql dumps.

### Icinga Web 2 Manual Database Setup <a id="web-setup-manual-from-source-database"></a>

Create the database and add a new user as shown below for MySQL/MariaDB:

```
sudo mysql -p

CREATE DATABASE icingaweb2;
GRANT CREATE, SELECT, INSERT, UPDATE, DELETE, DROP, ALTER, CREATE VIEW, INDEX, EXECUTE ON icingaweb2.* TO 'icingaweb2'@'localhost' IDENTIFIED BY 'icingaweb2';
quit

mysql -p icingaweb2 < /usr/share/icingaweb2/schema/mysql.schema.sql
```


Then generate a new password hash as described in the [authentication docs](05-Authentication.md#authentication-configuration-db-setup)
and use it to insert a new user called `icingaadmin` into the database.

```
mysql -p icingaweb2

INSERT INTO icingaweb_user (name, active, password_hash) VALUES ('icingaadmin', 1, '$1$EzxLOFDr$giVx3bGhVm4lDUAw6srGX1');
quit
```

### Icinga Web 2 Manual Configuration <a id="web-setup-manual-from-source-config"></a>


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

### Icinga Web 2 Manual Configuration Monitoring Module <a id="web-setup-manual-from-source-config-monitoring-module"></a>


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

### Icinga Web 2 Manual Setup Login <a id="web-setup-manual-from-source-login"></a>

Finally visit Icinga Web 2 in your browser to login as `icingaadmin` user: `/icingaweb2`.

## Automating the Installation of Icinga Web 2 <a id="web-setup-automation"></a>

Prior to creating your own script, please look into the official resources
which may help you already:

* [Puppet module](https://icinga.com/products/integrations/puppet/)
* [Chef cookbook](https://icinga.com/products/integrations/chef/)

If you are automating the installation of Icinga Web 2, you may want to skip the wizard and do things yourself.
These are the steps you'd need to take assuming you are using MySQL/MariaDB. If you are using PostgreSQL please adapt
accordingly. Note you need to have successfully completed the Icinga 2 installation, installed the Icinga Web 2 packages
and all the other steps described above first.

1. Install PHP dependencies: `php`, `php-intl`, `php-imagick`, `php-gd`, `php-mysql`, `php-curl`, `php-mbstring` used
by Icinga Web 2.
2. Create a database for Icinga Web 2, i.e. `icingaweb2`.
3. Import the database schema: `mysql -D icingaweb2 < /usr/share/icingaweb2/schema/mysql.schema.sql`.
4. Insert administrator user in the `icingaweb2` database:
`INSERT INTO icingaweb_user (name, active, password_hash) VALUES ('admin', 1, '<hash>')`, where `<hash>` is the output
of `php -r 'echo password_hash("yourtopsecretpassword", PASSWORD_DEFAULT);'`.
5. Make sure the `ido-mysql` and `api` features are enabled in Icinga 2: `icinga2 feature enable ido-mysql` and
`icinga2 feature enable api`.
6. Generate Apache/nginx config. This command will print an apache config for you on stdout:
`icingacli setup config webserver apache`. Similarly for nginx. You need to place that configuration in the right place,
for example `/etc/apache2/sites-enabled/icingaweb2.conf`.
7. Add `www-data` user to `icingaweb2` group if not done already (`usermod -a -G icingaweb2 www-data`).
8. Create the Icinga Web 2 configuration in `/etc/icingaweb2`. The directory can be easily created with:
`icingacli setup config directory`. This command ensures that the directory has the appropriate ownership and
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

## Kiosk Mode Configuration <a id="kiosk-mode"></a>

Be aware that when you create a kiosk user every person who has access to the kiosk is able to perform tasks on it.
Therefore you would need to create a user in the `roles.ini` in `/etc/icingaweb2/roles.ini`.

```
[kioskusers]
users = "kiosk"
```

If you need special permissions you should add those permissions to the user via the admin account in icingaweb2 to the role of the kiosk user.

For the Dashboard system where you want to display the kiosk you can add also the following part into the `icingaweb2.conf`.
So it starts directly into the kiosk mode.
If you want to show a specific Dashboard you can enforce this onto the kiosk user via the [enforceddashboard](https://github.com/Thomas-Gelf/icingaweb2-module-enforceddashboard) module.

```
<ifmodule mod_authz_core.c>
    # Apache 2.4
    SetEnvIf Remote_Addr "X.X.X.X" REMOTE_USER=kiosk
    <requireall>
        Require all granted
    </requireall>
</ifmodule>
```

Replace Remote_Addr with the IP where the kiosk user is accessing the Web to restrict further usage from other IPs.

Using the `REMOTE_USER` variable also requires adding an external backend to `authentication.ini`, as shown [here](05-Authentication.md#external-authentication-)


## Customizing the Landing Page <a id="landing-page"></a>

The default landing page of `dashboard` can be customized using the environment variable `ICINGAWEB_LANDING_PAGE`.

Example on RHEL/CentOS:

```
vim /etc/httpd/conf.d/web.icinga.com.conf

<VirtualHost *:80>

  ...

  <Directory "/usr/share/icingaweb2/public">

      ...

      SetEnv ICINGAWEB_LANDING_PAGE "icingadb/services/grid?problems"

      ...

  </Directory>
</VirtualHost>
```
