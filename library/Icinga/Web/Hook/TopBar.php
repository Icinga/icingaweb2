<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Hook;

use \Icinga\Web\Request;
use \Zend_View;

/**
 * Hook to extend topbar items
 */
interface TopBar
{
    /**
     * Function to generate top bar content
     *
     * @param   Request     $request
     * @param   Zend_View   $view
     *
     * @return  string
     */
    public function getHtml($request, $view);
}
