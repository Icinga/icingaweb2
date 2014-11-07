<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Setup\Webserver;

/**
 * Generate apache2.4 configuration
 */
class Apache24 extends Webserver
{
    /**
     * Use default template and change granted syntax for 2.4
     *
     * @return array
     */
    protected function getTemplate()
    {
        return  <<<'EOD'
Alias {webPath} {publicPath}
<directory {publicPath}>
  Options -Indexes
  AllowOverride All
  Require all granted
  EnableSendfile Off
</directory>
EOD;
    }
}
