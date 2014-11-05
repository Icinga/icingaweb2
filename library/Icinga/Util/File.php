<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

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
     * Create a file with an access mode
     *
     * @param   string  $path           The path to the file
     * @param   int     $accessMode     The access mode to set
     *
     * @throws  RuntimeException        In case the file cannot be created or the access mode cannot be set
     */
    public static function create($path, $accessMode)
    {
        if (!@touch($path)) {
            throw new RuntimeException('Cannot create file "' . $path . '" with acces mode "' . $accessMode . '"');
        }

        if (!@chmod($path, $accessMode)) {
            throw new RuntimeException('Cannot set access mode "' . $accessMode . '" on file "' . $path . '"');
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
