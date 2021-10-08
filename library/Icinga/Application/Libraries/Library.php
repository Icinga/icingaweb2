<?php
/* Icinga Web 2 | (c) 2020 Icinga GmbH | GPLv2+ */

namespace Icinga\Application\Libraries;

use CallbackFilterIterator;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\Json\JsonDecodeException;
use Icinga\Util\Json;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Library
{
    /** @var string */
    protected $path;

    /** @var string */
    protected $jsAssetPath;

    /** @var string */
    protected $cssAssetPath;

    /** @var string */
    protected $staticAssetPath;

    /** @var string */
    protected $version;

    /** @var array */
    protected $metaData;

    /** @var array */
    protected $assets;

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
     * Get this library's path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get path of this library's JS assets
     *
     * @return string
     */
    public function getJsAssetPath()
    {
        $this->assets();
        return $this->jsAssetPath;
    }

    /**
     * Get path of this library's CSS assets
     *
     * @return string
     */
    public function getCssAssetPath()
    {
        $this->assets();
        return $this->cssAssetPath;
    }

    /**
     * Get path of this library's static assets
     *
     * @return string
     */
    public function getStaticAssetPath()
    {
        $this->assets();
        return $this->staticAssetPath;
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
     * Check whether the given package is required
     *
     * @param string $vendor The vendor of the project
     * @param string $project The project's name
     *
     * @return bool
     */
    public function isRequired($vendor, $project)
    {
        // Ensure the parts are lowercase and separated by dashes, not capital letters
        $project = strtolower(join('-', preg_split('/\w(?=[A-Z])/', $project)));

        return isset($this->metaData()['require'][strtolower($vendor) . '/' . $project]);
    }

    /**
     * Get this library's JS assets
     *
     * @return string[] Asset paths
     */
    public function getJsAssets()
    {
        return $this->assets()['js'];
    }

    /**
     * Get this library's CSS assets
     *
     * @return string[] Asset paths
     */
    public function getCssAssets()
    {
        return $this->assets()['css'];
    }

    /**
     * Get this library's static assets
     *
     * @return string[] Asset paths
     */
    public function getStaticAssets()
    {
        return $this->assets()['static'];
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
            $metaData = @file_get_contents($this->path . DIRECTORY_SEPARATOR . 'composer.json');
            if ($metaData === false) {
                throw new ConfigurationError('Library at "%s" is not a composerized project', $this->path);
            }

            $this->metaData = Json::decode($metaData, true);
        }

        return $this->metaData;
    }

    /**
     * Register and return this library's assets
     *
     * @return array
     */
    protected function assets()
    {
        if ($this->assets !== null) {
            return $this->assets;
        }

        $listAssets = function ($type) {
            $dir = join(DIRECTORY_SEPARATOR, [$this->path, 'asset', $type]);
            if (! is_dir($dir)) {
                return [];
            }

            $this->{$type . 'AssetPath'} = $dir;

            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
                $dir,
                RecursiveDirectoryIterator::CURRENT_AS_FILEINFO | RecursiveDirectoryIterator::SKIP_DOTS
            ));
            if ($type === 'static') {
                return $iterator;
            }

            return new CallbackFilterIterator(
                $iterator,
                function ($path) use ($type) {
                    if ($type === 'js' && $path->getExtension() === 'js') {
                        return substr($path->getPathname(), -5 - strlen($type)) !== ".min.$type";
                    } elseif ($type === 'css'
                        && ($path->getExtension() === 'css' || $path->getExtension() === 'less')
                    ) {
                        return substr($path->getPathname(), -5 - strlen($type)) !== ".min.$type";
                    }

                    return false;
                }
            );
        };

        $this->assets = [];

        $jsAssets = $listAssets('js');
        $this->assets['js'] = is_array($jsAssets) ? $jsAssets : iterator_to_array($jsAssets);

        $cssAssets = $listAssets('css');
        $this->assets['css'] = is_array($cssAssets) ? $cssAssets : iterator_to_array($cssAssets);

        $staticAssets = $listAssets('static');
        $this->assets['static'] = is_array($staticAssets) ? $staticAssets : iterator_to_array($staticAssets);

        return $this->assets;
    }
}
