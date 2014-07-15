<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Application;

require_once dirname(__FILE__) . '/ApplicationBootstrap.php';

use Icinga\Exception\ProgrammingError;

/**
 * Use this if you want to make use of Icinga funtionality in other web projects
 *
 * Usage example:
 * <code>
 * use Icinga\Application\EmbeddedWeb;
 * EmbeddedWeb::start();
 * </code>
 */
class EmbeddedWeb extends ApplicationBootstrap
{
    /**
     * Embedded bootstrap parts
     *
     * @see    ApplicationBootstrap::bootstrap
     * @return self
     */
    protected function bootstrap()
    {
        return $this->loadConfig()
            ->setupErrorHandling()
            ->setupTimezone()
            ->setupModuleManager()
            ->loadEnabledModules();
    }
}
