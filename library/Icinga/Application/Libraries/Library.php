<?php
/* Icinga Web 2 | (c) 2020 Icinga GmbH | GPLv2+ */

namespace Icinga\Application\Libraries;

use Icinga\Exception\ConfigurationError;
use Icinga\Exception\Json\JsonDecodeException;
use Icinga\Util\Json;

class Library
{
    protected $path;

    /** @var string */
    protected $version;

    /** @var array */
    protected $metaData;

    /**
     * Create a new Library
     *
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * Get this library's name
     *
     * @return string
     */
    public function getName()
    {
        return $this->metaData()['name'];
    }

    /**
     * Get this library's version
     *
     * @return string
     */
    public function getVersion()
    {
        if ($this->version === null) {
            if (isset($this->metaData()['version'])) {
                $this->version = trim(ltrim($this->metaData()['version'], 'v'));
            } else {
                $versionFile = $this->path . DIRECTORY_SEPARATOR . 'VERSION';
                if (file_exists($versionFile)) {
                    $this->version = trim(ltrim(file_get_contents($versionFile), 'v'));
                } else {
                    $this->version = '';
                }
            }
        }

        return $this->version;
    }

    /**
     * Register this library's autoloader
     *
     * @return void
     */
    public function registerAutoloader()
    {
        $autoloaderPath = join(DIRECTORY_SEPARATOR, [$this->path, 'vendor', 'autoload.php']);
        if (file_exists($autoloaderPath)) {
            require_once $autoloaderPath;
        }
    }

    /**
     * Parse and return this library's metadata
     *
     * @return array
     *
     * @throws ConfigurationError
     * @throws JsonDecodeException
     */
    protected function metaData()
    {
        if ($this->metaData === null) {
            $metaData = file_get_contents($this->path . DIRECTORY_SEPARATOR . 'composer.json');
            if ($metaData === false) {
                throw new ConfigurationError('Library at "%s" is not a composerized project', $this->path);
            }

            $this->metaData = Json::decode($metaData, true);
        }

        return $this->metaData;
    }
}
