<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Config\Webserver;

/**
 * Generate apache 2.x (< 2.4) configuration
 */
class Apache2 extends WebServer
{
    /**
     * @return array
     */
    protected function getTemplate()
    {
        return array(
            'Alias {webPath} {publicPath}',
            '<directory {publicPath}>',
            '  Options -Indexes',
            '  AllowOverride All',
            '  Order allow,deny',
            '  Allow from all',
            '  EnableSendfile Off',
            '</directory>'
        );
    }
}
