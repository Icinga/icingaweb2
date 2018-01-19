<?php
/* Icinga Web 2 | (c) 2018 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Web\Helper;

use Icinga\Web\Helper\HtmlPurifier;

class PluginOutputPurifier extends HtmlPurifier
{
    protected function configure($config)
    {
        $config->set(
            'HTML.Allowed',
            'p,br,b,a[href|target],i,ul,ol,li,table,tr,th[colspan],td[colspan],div,*[class]'
        );
    }
}
