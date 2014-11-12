<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

if (! @include_once dirname(__DIR__) . '/library/Icinga/Application/webrouter.php') {
    // If the Icinga library wasn't found under ICINGAWEB_BASEDIR, require that the Icinga library is found in PHP's
    // include path which is the case if Icinga Web 2 is installed via packages
    require_once 'Icinga/Application/webrouter.php';
}
