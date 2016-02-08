<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Util;

use SplFileObject;
use ErrorException;
use RuntimeException;
use Icinga\Exception\NotWritableError;

/**
 * File
 *
 * A class to ease opening files and reading/writing to them.
 */
class File extends SplFileObject
{
    /**
     * The mode used to open the file
     *
     * @var string
     */
    protected $openMode;

    /**
     * The access mode to use when creating directories
     *
     * @var int
     */
    public static $dirMode = 1528; // 2770

    /**
     * @see SplFileObject::__construct()
     */
    public function __construct($filename, $openMode = 'r', $useIncludePath = false, $context = null)
    {
        $this->openMode = $openMode;
        if ($context === null) {
            parent::__construct($filename, $openMode, $useIncludePath);
        } else {
            parent::__construct($filename, $openMode, $useIncludePath, $context);
        }
    }

    /**
     * Create a file using the given access mode and return a instance of File open for writing
     *
     * @param   string  $path           The path to the file
     * @param   int     $accessMode     The access mode to set
     * @param   bool    $recursive      Whether missing nested directories of the given path should be created
     *
     * @return  File
     *
     * @throws  RuntimeException        In case the file cannot be created or the access mode cannot be set
     * @throws  NotWritableError        In case the path's (existing) parent is not writable
     */
    public static function create($path, $accessMode, $recursive = true)
    {
        $dirPath = dirname($path);
        if ($recursive && !is_dir($dirPath)) {
            static::createDirectories($dirPath);
        } elseif (! is_writable($dirPath)) {
            throw new NotWritableError(sprintf('Path "%s" is not writable', $dirPath));
        }

        $file = new static($path, 'x+');

        if (! @chmod($path, $accessMode)) {
            $error = error_get_last();
            throw new RuntimeException(sprintf(
                'Cannot set access mode "%s" on file "%s" (%s)',
                decoct($accessMode),
                $path,
                $error['message']
            ));
        }

        return $file;
    }

    /**
     * Create missing directories
     *
     * @param   string  $path
     *
     * @throws  RuntimeException        In case a directory cannot be created or the access mode cannot be set
     */
    protected static function createDirectories($path)
    {
        $part = strpos($path, DIRECTORY_SEPARATOR) === 0 ? DIRECTORY_SEPARATOR : '';
        foreach (explode(DIRECTORY_SEPARATOR, ltrim($path, DIRECTORY_SEPARATOR)) as $dir) {
            $part .= $dir . DIRECTORY_SEPARATOR;

            if (! is_dir($part)) {
                if (! @mkdir($part, static::$dirMode)) {
                    $error = error_get_last();
                    throw new RuntimeException(sprintf(
                        'Failed to create missing directory "%s" (%s)',
                        $part,
                        $error['message']
                    ));
                }

                if (! @chmod($part, static::$dirMode)) {
                    $error = error_get_last();
                    throw new RuntimeException(sprintf(
                        'Failed to set access mode "%s" for directory "%s" (%s)',
                        decoct(static::$dirMode),
                        $part,
                        $error['message']
                    ));
                }
            }
        }
    }

    /**
     * @see SplFileObject::fwrite()
     */
    public function fwrite($str, $length = null)
    {
        $this->assertOpenForWriting();
        $this->setupErrorHandler();
        $retVal = $length === null ? parent::fwrite($str) : parent::fwrite($str, $length);
        restore_error_handler();
        return $retVal;
    }

    /**
     * @see SplFileObject::ftruncate()
     */
    public function ftruncate($size)
    {
        $this->assertOpenForWriting();
        $this->setupErrorHandler();
        $retVal = parent::ftruncate($size);
        restore_error_handler();
        return $retVal;
    }

    /**
     * @see SplFileObject::ftell()
     */
    public function ftell()
    {
        $this->setupErrorHandler();
        $retVal = parent::ftell();
        restore_error_handler();
        return $retVal;
    }

    /**
     * @see SplFileObject::flock()
     */
    public function flock($operation, &$wouldblock = null)
    {
        $this->setupErrorHandler();
        $retVal = parent::flock($operation, $wouldblock);
        restore_error_handler();
        return $retVal;
    }

    /**
     * @see SplFileObject::fgetc()
     */
    public function fgetc()
    {
        $this->setupErrorHandler();
        $retVal = parent::fgetc();
        restore_error_handler();
        return $retVal;
    }

    /**
     * @see SplFileObject::fflush()
     */
    public function fflush()
    {
        $this->setupErrorHandler();
        $retVal = parent::fflush();
        restore_error_handler();
        return $retVal;
    }

    /**
     * Setup an error handler that throws a RuntimeException for every emitted E_WARNING
     */
    protected function setupErrorHandler()
    {
        set_error_handler(
            function ($errno, $errstr, $errfile, $errline) {
                restore_error_handler();
                throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
            },
            E_WARNING
        );
    }

    /**
     * Assert that the file was opened for writing and throw an exception otherwise
     *
     * @throws  NotWritableError    In case the file was not opened for writing
     */
    protected function assertOpenForWriting()
    {
        if (!preg_match('@w|a|\+@', $this->openMode)) {
            throw new NotWritableError('File not open for writing');
        }
    }
}
