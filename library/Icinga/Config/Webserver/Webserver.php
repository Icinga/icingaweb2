<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Config\Webserver;

use Icinga\Application\ApplicationBootstrap;
use Icinga\Exception\ProgrammingError;

/**
 * Generate webserver configuration
 */
abstract class WebServer
{
    /**
     * Web path
     *
     * @var string
     */
    protected $webPath;

    /**
     * SAPI name (e.g for cgi config generation)
     *
     * @var string
     */
    protected $sapi;

    /**
     * System path to public documents
     *
     * @var string
     */
    protected $publicPath;

    /**
     * Application
     *
     * @var ApplicationBootstrap
     */
    protected $app;

    /**
     * Create instance by type name
     *
     * @param   string $type
     *
     * @return  WebServer
     *
     * @throws  ProgrammingError
     */
    public static function createInstance($type)
    {
        $class = __NAMESPACE__ . '\\' . ucfirst($type);
        if (class_exists($class)) {
            return new $class();
        }
        throw new ProgrammingError('Class "%s" does not exist', $class);
    }

    /**
     * Generate configuration
     *
     * @return string
     */
    public function generate()
    {
        $template = $this->getTemplate();
        if (is_array($template)) {
            $template = implode(PHP_EOL, $template);
        }
        $searchTokens = array(
            '{webPath}',
            '{publicPath}'
        );
        $replaceTokens = array(
            $this->getWebPath(),
            $this->getPublicPath()
        );
        $template = str_replace($searchTokens, $replaceTokens, $template);
        return $template;
    }

    /**
     * Specific template
     *
     * @return array|string
     */
    abstract protected function getTemplate();

    /**
     * Setter for SAPI name
     *
     * @param string $sapi
     */
    public function setSapi($sapi)
    {
        $this->sapi = $sapi;
    }

    /**
     * Getter for SAPI name
     *
     * @return string
     */
    public function getSapi()
    {
        return $this->sapi;
    }

    /**
     * Setter for web path
     *
     * @param string $webPath
     */
    public function setWebPath($webPath)
    {
        $this->webPath = $webPath;
    }

    /**
     * Getter for web path
     *
     * @return string
     */
    public function getWebPath()
    {
        return $this->webPath;
    }

    /**
     * @param string $publicPath
     */
    public function setPublicPath($publicPath)
    {
        $this->publicPath = $publicPath;
    }

    /**
     * Detect public root
     *
     * @return string
     */
    public function detectPublicPath()
    {
        $applicationPath = $this->getApp()->getApplicationDir();
        $applicationPath = dirname($applicationPath) . DIRECTORY_SEPARATOR . 'public';
        if (is_dir($applicationPath) === true) {
            return $applicationPath;
        }
        return null;
    }

    /**
     * Getter for public root
     *
     * @return string
     */
    public function getPublicPath()
    {
        if ($this->publicPath === null) {
            $this->publicPath = $this->detectPublicPath();
        }
        return $this->publicPath;
    }

    /**
     * Setter for application bootstrap
     *
     * @param ApplicationBootstrap $app
     */
    public function setApp(ApplicationBootstrap $app)
    {
        $this->app = $app;
    }

    /**
     * Getter for application bootstrap
     *
     * @return ApplicationBootstrap
     */
    public function getApp()
    {
        return $this->app;
    }
}
