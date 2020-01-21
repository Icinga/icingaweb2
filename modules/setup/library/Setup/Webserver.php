<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup;

use Icinga\Application\Icinga;
use Icinga\Exception\ProgrammingError;

/**
 * Base class for generating webserver configuration
 */
abstract class Webserver
{
    /**
     * Document root
     *
     * @var string
     */
    protected $documentRoot;

    /**
     * URL path of Icinga Web 2
     *
     * @var string
     */
    protected $urlPath = '/icingaweb2';

    /**
     * Path to Icinga Web 2's configuration files
     *
     * @var string
     */
    protected $configDir;

    /**
     * Address or path where to pass requests to FPM
     *
     * @var string
     */
    protected $fpmUri;

    /**
     * Enable to pass requests to FPM
     *
     * @var bool
     */
    protected $enableFpm = false;

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
        $class = __NAMESPACE__ . '\\Webserver\\' . ucfirst($type);
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

        $searchTokens = array(
            '{urlPath}',
            '{documentRoot}',
            '{configDir}',
            '{fpmUri}'
        );
        $replaceTokens = array(
            $this->getUrlPath(),
            $this->getDocumentRoot(),
            $this->getConfigDir(),
            $this->getFpmUri()
        );
        $template = str_replace($searchTokens, $replaceTokens, $template);
        return $template;
    }

    /**
     * Specific template
     *
     * @return string
     */
    abstract protected function getTemplate();

    /**
     * Set the URL path of Icinga Web 2
     *
     * @param   string $urlPath
     *
     * @return  $this
     */
    public function setUrlPath($urlPath)
    {
        $this->urlPath = '/' . ltrim(trim((string) $urlPath), '/');
        return $this;
    }

    /**
     * Get the URL path of Icinga Web 2
     *
     * @return string
     */
    public function getUrlPath()
    {
        return $this->urlPath;
    }

    /**
     * Set the document root
     *
     * @param   string $documentRoot
     *
     * @return  $this
     */
    public function setDocumentRoot($documentRoot)
    {
        $this->documentRoot = trim((string) $documentRoot);
        return $this;
    }

    /**
     * Detect the document root
     *
     * @return string
     */
    public function detectDocumentRoot()
    {
        return Icinga::app()->getBaseDir('public');
    }

    /**
     * Get the document root
     *
     * @return string
     */
    public function getDocumentRoot()
    {
        if ($this->documentRoot === null) {
            $this->documentRoot = $this->detectDocumentRoot();
        }
        return $this->documentRoot;
    }

    /**
     * Set the configuration directory
     *
     * @param   string  $configDir
     *
     * @return  $this
     */
    public function setConfigDir($configDir)
    {
        $this->configDir = (string) $configDir;
        return $this;
    }

    /**
     * Get the configuration directory
     *
     * @return string
     */
    public function getConfigDir()
    {
        if ($this->configDir === null) {
            return Icinga::app()->getConfigDir();
        }
        return $this->configDir;
    }

    /**
     * Get whether FPM is enabled
     *
     * @return  bool
     */
    public function getEnableFpm()
    {
        return $this->enableFpm;
    }

    /**
     * Set FPM enabled
     *
     * @param   bool  $flag
     *
     * @return  $this
     */
    public function setEnableFpm($flag)
    {
        $this->enableFpm = (bool) $flag;

        return $this;
    }

    /**
     * Get the address or path where to pass requests to FPM
     *
     * @return  string
     */
    public function getFpmUri()
    {
        return $this->fpmUri;
    }

    /**
     * Set the address or path where to pass requests to FPM
     *
     * @param   string  $uri
     *
     * @return  $this
     */
    public function setFpmUri($uri)
    {
        $this->fpmUri = (string) $uri;

        return $this;
    }
}
