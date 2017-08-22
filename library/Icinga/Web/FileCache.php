<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Web;

class FileCache
{
    /**
     * FileCache singleton instances
     *
     * @var array
     */
    protected static $instances = array();

    /**
     * Cache instance base directory
     *
     * @var string
     */
    protected $basedir;

    /**
     * Instance name
     *
     * @var string
     */
    protected $name;

    /**
     * Whether the cache is enabled
     *
     * @var bool
     */
    protected $enabled = false;

    /**
     * The protected constructor creates a new instance with the given name
     *
     * @param string $name Cache instance name
     */
    protected function __construct($name)
    {
        $this->name = $name;
        $tmpDir = sys_get_temp_dir();
        $runtimePath = $tmpDir . '/FileCache_' . $name;
        if (is_dir($runtimePath)) {
            // Don't combine the following if with the above because else the elseif path will be evaluated if the
            // runtime path exists and is not writeable
            if (is_writeable($runtimePath)) {
                $this->basedir = $runtimePath;
                $this->enabled = true;
            }
        } elseif (is_dir($tmpDir) && is_writeable($tmpDir) && @mkdir($runtimePath, octdec('1750'), true)) {
            // Suppress mkdir errors because it may error w/ no such file directory if the systemd private tmp directory
            // for the web server has been removed
            $this->basedir = $runtimePath;
            $this->enabled = true;
        }
    }

    /**
     * Store the given content to the desired file name
     *
     * @param string $file    new (relative) filename
     * @param string $content the content to be stored
     *
     * @return bool whether the file has been stored
     */
    public function store($file, $content)
    {
        if (! $this->enabled) {
            return false;
        }

        return file_put_contents($this->filename($file), $content);
    }

    /**
     * Find out whether a given file exists
     *
     * @param string $file      the (relative) filename
     * @param int    $newerThan optional timestamp to compare against
     *
     * @return bool whether such file exists
     */
    public function has($file, $newerThan = null)
    {
        if (! $this->enabled) {
            return false;
        }

        $filename = $this->filename($file);

        if (! file_exists($filename) || ! is_readable($filename)) {
            return false;
        }

        if ($newerThan === null) {
            return true;
        }

        $info = stat($filename);

        if ($info === false) {
            return false;
        }

        return (int) $newerThan < $info['mtime'];
    }

    /**
     * Get a specific file or false if no such file available
     *
     * @param string $file the disired file name
     *
     * @return string|bool Filename content or false
     */
    public function get($file)
    {
        if ($this->has($file)) {
            return file_get_contents($this->filename($file));
        }

        return false;
    }

    /**
     * Send a specific file to the browser (output)
     *
     * @param string $file the disired file name
     *
     * @return bool Whether the file has been sent
     */
    public function send($file)
    {
        if ($this->has($file)) {
            readfile($this->filename($file));

            return true;
        }

        return false;
    }

    /**
     * Get absolute filename for a given file
     *
     * @param string $file the disired file name
     *
     * @return string absolute filename
     */
    protected function filename($file)
    {
        return $this->basedir . '/' . $file;
    }

    /**
     * Whether the given ETag matches a cached file
     *
     * If no ETag is given we'll try to fetch the one from the current
     * HTTP request.
     *
     * @param string $file  The cached file you want to check
     * @param string $match The ETag to match against
     *
     * @return string|bool ETag on match, otherwise false
     */
    public function etagMatchesCachedFile($file, $match = null)
    {
        return self::etagMatchesFiles($this->filename($file), $match);
    }

    /**
     * Create an ETag for the given file
     *
     * @param string $file The desired cache file
     *
     * @return string your ETag
     */
    public function etagForCachedFile($file)
    {
        return self::etagForFiles($this->filename($file));
    }

    /**
     * Whether the given ETag matchesspecific file(s) on disk
     *
     * @param string|array $files file(s) to check
     * @param string       $match ETag to match against
     *
     * @return string|bool ETag on match, otherwise false
     */
    public static function etagMatchesFiles($files, $match = null)
    {
        if ($match === null) {
            $match = isset($_SERVER['HTTP_IF_NONE_MATCH'])
                ? trim($_SERVER['HTTP_IF_NONE_MATCH'], '"')
                : false;
        }
        if (! $match) {
            return false;
        }

        if (preg_match('/([0-9a-f]{8}-[0-9a-f]{8}-[0-9a-f]{8})-\w+/i', $match, $matches)) {
            // Removes compression suffixes as our custom algorithm can't handle compressed cache files anyway
            $match = $matches[1];
        }

        $etag = self::etagForFiles($files);
        return $match === $etag ? $etag : false;
    }

    /**
     * Create ETag for the given files
     *
     * Custom algorithm creating an ETag based on filenames, mtimes
     * and file sizes. Supports single files or a list of files. This
     * way we are able to create ETags for virtual files depending on
     * multiple source files (e.g. compressed JS, CSS).
     *
     * @param string|array $files Single file or a list of such
     *
     * @return string The generated ETag
     */
    public static function etagForFiles($files)
    {
        if (is_string($files)) {
            $files = array($files);
        }

        $sizes  = array();
        $mtimes = array();

        foreach ($files as $file) {
            $file = realpath($file);
            if ($file !== false && $info = stat($file)) {
                $mtimes[] = $info['mtime'];
                $sizes[]  = $info['size'];
            } else {
                $mtimes[] = time();
                $sizes[]  = 0;
            }
        }

        return sprintf(
            '%s-%s-%s',
            hash('crc32', implode('|', $files)),
            hash('crc32', implode('|', $sizes)),
            hash('crc32', implode('|', $mtimes))
        );
    }

    /**
     * Factory creating your cache instance
     *
     * @param string $name Instance name
     *
     * @return FileCache
     */
    public static function instance($name = 'icingaweb')
    {
        if ($name !== 'icingaweb') {
            $name = 'icingaweb/modules/' . $name;
        }

        if (!array_key_exists($name, self::$instances)) {
            self::$instances[$name] = new static($name);
        }

        return self::$instances[$name];
    }
}
