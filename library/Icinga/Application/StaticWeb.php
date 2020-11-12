<?php
/* Icinga Web 2 | (c) 2020 Icinga GmbH | GPLv2+ */

namespace Icinga\Application;

require_once dirname(__FILE__) . '/EmbeddedWeb.php';

class StaticWeb extends EmbeddedWeb
{
    protected function bootstrap()
    {
        return $this
            ->setupErrorHandling()
            ->loadLibraries()
            ->loadConfig()
            ->setupLogging()
            ->setupLogger()
            ->setupRequest()
            ->setupResponse();
    }
}
