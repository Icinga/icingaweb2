<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Setup\Webserver;

/**
 * Generate apache 2.x (< 2.4) configuration
 */
class Apache2 extends Webserver
{
    /**
     * @return array
     */
    protected function getTemplate()
    {
            return  <<<'EOD'
Alias {webPath} {publicPath}
<directory {publicPath}>
  Options -Indexes
  AllowOverride All
  Order allow,deny
  Allow from all
  EnableSendfile Off
</directory>
EOD;

    }
}
