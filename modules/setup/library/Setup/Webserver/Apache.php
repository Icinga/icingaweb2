<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Setup\Webserver;

use Icinga\Module\Setup\Webserver;

/**
 * Generate Apache 2.x configuration
 */
class Apache extends Webserver
{
    /**
     * @return array
     */
    protected function getTemplate()
    {
            return  <<<'EOD'
Alias {webPath} "{documentRoot}"

<Directory "{documentRoot}">
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

    SetEnv ICINGAWEB_CONFIGDIR /etc/icingaweb/

    EnableSendfile Off

    <IfModule mod_rewrite.c>
        RewriteEngine on
        RewriteBase {webPath}/
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
EOD;

    }
}
