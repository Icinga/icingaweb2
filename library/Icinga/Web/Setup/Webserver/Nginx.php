<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Setup\Webserver;

/**
 * Create nginx webserver configuration
 */
class Nginx extends Webserver
{
    /**
     * Specific template
     *
     * @return array|string
     */
    protected function getTemplate()
    {
        return <<<'EOD'
location ~ ^{webPath}/index\.php(.*)$ {
  # fastcgi_pass 127.0.0.1:9000;
  fastcgi_pass unix:/var/run/php5-fpm.sock;
  fastcgi_index index.php;
  include fastcgi_params;
  fastcgi_param SCRIPT_FILENAME {publicPath}/index.php;
}

location ~ ^{webPath} {
  alias {publicPath};
  index index.php;
  try_files $uri $uri/ {webPath}/index.php$is_args$args;
}
EOD;
    }
}
