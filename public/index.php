<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

define('ICINGAWEB_BASEDIR', dirname(__DIR__));
// ICINGAWEB_BASEDIR is the parent folder for at least application, bin, modules and public


if (! @include_once ICINGAWEB_BASEDIR . '/library/Icinga/Application/webrouter.php') {
    // If the Icinga library wasn't found under ICINGAWEB_BASEDIR, require that the Icinga library is found in PHP's
    // include path which is the case if Icinga Web 2 is installed via packages
    require_once 'Icinga/Application/webrouter.php';
}
