<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Application;

require_once dirname(__FILE__) . '/ApplicationBootstrap.php';

use Icinga\Web\Request;
use Icinga\Web\Response;
use ipl\I18n\NoopTranslator;
use ipl\I18n\StaticTranslator;

/**
 * Use this if you want to make use of Icinga functionality in other web projects
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
     * Request
     *
     * @var Request
     */
    protected $request;

    /**
     * Response
     *
     * @var Response
     */
    protected $response;

    /**
     * Get the request
     *
     * @return  Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Get the response
     *
     * @return  Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Embedded bootstrap parts
     *
     * @see    ApplicationBootstrap::bootstrap
     *
     * @return $this
     */
    protected function bootstrap()
    {
        return $this
            ->setupErrorHandling()
            ->loadLibraries()
            ->loadConfig()
            ->setupLogging()
            ->setupLogger()
            ->setupRequest()
            ->setupResponse()
            ->setupTimezone()
            ->prepareFakeInternationalization()
            ->setupModuleManager()
            ->loadEnabledModules();
    }

    /**
     * Set the request
     *
     * @return  $this
     */
    protected function setupRequest()
    {
        $this->request = new Request();
        return $this;
    }

    /**
     * Set the response
     *
     * @return  $this
     */
    protected function setupResponse()
    {
        $this->response = new Response();
        return $this;
    }

    /**
     * Prepare fake internationalization
     *
     * @return $this
     */
    protected function prepareFakeInternationalization()
    {
        StaticTranslator::$instance = new NoopTranslator();

        return $this;
    }
}
