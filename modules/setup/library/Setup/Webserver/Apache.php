<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup\Webserver;

use Icinga\Module\Setup\Webserver;

/**
 * Generate Apache 2.x configuration
 */
class Apache extends Webserver
{
    protected $fpmUri = 'fcgi://127.0.0.1:9000';

    protected function getTemplate()
    {
            return  <<<'EOD'
Alias {urlPath} "{documentRoot}"

# Remove comments if you want to use PHP FPM and your Apache version is older than 2.4
#<IfVersion < 2.4>
#    # Forward PHP requests to FPM
#    SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
#    <LocationMatch "^{urlPath}/(.*\.php)$">
#        ProxyPassMatch "fcgi://127.0.0.1:9000/{documentRoot}/$1"
#    </LocationMatch>
#</IfVersion>

<Directory "{documentRoot}">
    Options SymLinksIfOwnerMatch
    AllowOverride None

    DirectoryIndex index.php

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

    SetEnv ICINGAWEB_CONFIGDIR "{configDir}"

    EnableSendfile Off

    <IfModule mod_rewrite.c>
        RewriteEngine on
        RewriteBase {urlPath}/
        RewriteCond %{REQUEST_FILENAME} -s [OR]
        RewriteCond %{REQUEST_FILENAME} -l [OR]
        RewriteCond %{REQUEST_FILENAME} -d
        RewriteRule ^.*$ - [NC,L]
        RewriteRule ^.*$ index.php [NC,L]
    </IfModule>

    <IfModule !mod_rewrite.c>
        DirectoryIndex error_norewrite.html
        ErrorDocument 404 {urlPath}/error_norewrite.html
    </IfModule>

# Remove comments if you want to use PHP FPM and your Apache version
# is greater than or equal to 2.4
#    <IfVersion >= 2.4>
#        # Forward PHP requests to FPM
#        SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
#        <FilesMatch "\.php$">
#            SetHandler "proxy:{fpmUri}"
#            ErrorDocument 503 {urlPath}/error_unavailable.html
#        </FilesMatch>
#    </IfVersion>
</Directory>
EOD;
    }
}
