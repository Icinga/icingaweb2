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
     * Address where to pass requests to FPM
     *
     * @var string
     */
    protected $fpmUrl;

    /**
     * Socket path where to pass requests to FPM
     *
     * @var string
     */
    protected $fpmSocketPath;

    /**
     * FPM socket connection schema
     *
     * @var string
     */
    protected $fpmSocketSchema = 'unix:';

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
     * @return  Webserver
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
        $fpmUri = $this->createFpmUri();

        $searchTokens = array(
            '{urlPath}',
            '{documentRoot}',
            '{aliasDocumentRoot}',
            '{configDir}',
            '{fpmUri}'
        );
        $replaceTokens = array(
            $this->getUrlPath(),
            $this->getDocumentRoot(),
            preg_match('~/$~', $this->getUrlPath()) ? $this->getDocumentRoot() . '/' : $this->getDocumentRoot(),
            $this->getConfigDir(),
            $fpmUri
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
     * Creates the connection string for the respective web server
     *
     * @return string
     */
    abstract protected function createFpmUri();

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
     * Get the address where to pass requests to FPM
     *
     * @return  string
     */
    public function getFpmUrl()
    {
        return $this->fpmUrl;
    }

    /**
     * Set the address where to pass requests to FPM
     *
     * @param string $url
     *
     * @return  $this
     */
    public function setFpmUrl($url)
    {
        $this->fpmUrl = (string) $url;

        return $this;
    }

    /**
     * Get the socket path where to pass requests to FPM
     *
     * @return  string
     */
    public function getFpmSocketPath()
    {
        return $this->fpmSocketPath;
    }

    /**
     * Set the socket path where to pass requests to FPM
     *
     * @return  $this
     */
    public function setFpmSocketPath($socketPath)
    {
        $this->fpmSocketPath = (string) $socketPath;

        return $this;
    }
}
