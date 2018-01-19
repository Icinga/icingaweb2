<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup\Webserver;

use Icinga\Module\Setup\Webserver;

/**
 * Generate nginx configuration
 */
class Nginx extends Webserver
{
    protected $fpmUri = '127.0.0.1:9000';

    protected function getTemplate()
    {
        return <<<'EOD'
location ~ ^{urlPath}/index\.php(.*)$ {
  fastcgi_pass {fpmUri};
  fastcgi_index index.php;
  include fastcgi_params;
  fastcgi_param SCRIPT_FILENAME {documentRoot}/index.php;
  fastcgi_param ICINGAWEB_CONFIGDIR {configDir};
  fastcgi_param REMOTE_USER $remote_user;
}

location ~ ^{urlPath}(.+)? {
  alias {documentRoot};
  index index.php;
  try_files $1 $uri $uri/ {urlPath}/index.php$is_args$args;
}
EOD;
    }
}
