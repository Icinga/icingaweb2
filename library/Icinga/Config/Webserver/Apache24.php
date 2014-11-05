<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Config\Webserver;

/**
 * Generate apache2.4 configuration
 */
class Apache24 extends Apache2
{
    /**
     * Use default template and change granted syntax for 2.4
     *
     * @return array
     */
    protected function getTemplate()
    {
        $template = parent::getTemplate();
        $replace = array(
            '  Require all granted'
        );
        array_splice($template, count($template)-4, 2, $replace);
        return $template;
    }
}
