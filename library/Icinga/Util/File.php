<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Util;

use Exception;
use Icinga\Exception\ProgrammingError;

/**
 * File
 *
 * A class to ease opening files and reading/writing to them.
 */
class File
{
    /**
     * The location of the file
     *
     * @var string
     */
    protected $path;

    /**
     * The file resource
     *
     * @var resource
     */
    protected $handle;

    /**
     * The file access mode to set
     *
     * Gets set/updated after the file has been closed.
     *
     * @var int
     */
    protected $accessMode;

    /**
     * Open a file
     *
     * @param   string  $path       The location of the file
     * @param   int     $openMode   The open mode to use
     */
    protected function __construct($path, $openMode)
    {
        $this->setupErrorHandler();
        $this->handle = fopen($path, $openMode);
        $this->resetErrorHandler();
    }

    /**
     * Open a file
     *
     * @param   string  $path       The location of the file
     * @param   int     $openMode   The open mode to use
     *
     * @return  File
     */
    public static function open($path, $openMode = 'r')
    {
        return new static($path, $openMode);
    }

    /**
     * Read contents of file
     *
     * @param   int|null    $length     Read up to $length bytes or until EOF if null
     *
     * @return  string
     */
    public function read($length = null)
    {
        $this->setupErrorHandler();
        $content = stream_get_contents($this->handle, $length !== null ? $length : -1);
        $this->resetErrorHandler();
        return $content;
    }

    /**
     * Write contents to file
     *
     * @param   string  $bytes      The contents to write
     *
     * @return  self
     */
    public function write($bytes)
    {
        $this->setupErrorHandler();

        $written = 0;
        while ($bytes) {
            $justWritten = fwrite($this->handle, $bytes);

            if ($justWritten === 0) {
                throw new Exception('Failed to write to open file.');
            }

            $bytes = substr($bytes, $justWritten);
            $written += $justWritten;
        }

        $this->resetErrorHandler();
        return $this;
    }

    /**
     * Change access mode of file
     *
     * Note that the access mode cannot be changed until the file is still open.
     *
     * @param   int     $accessMode     The access mode to set
     *
     * @return  self
     */
    public function chmod($accessMode)
    {
        $this->accessMode = $accessMode;
        return $this;
    }

    /**
     * Close the file
     *
     * @throws  ProgrammingError    In case the file is already closed or its resource became invalid
     */
    public function close()
    {
        if (!is_resource($this->handle)) {
            throw new ProgrammingError('Tried to close an invalid file resource or an already closed file');
        }

        fclose($this->handle);

        if ($this->accessMode !== null) {
            $this->setupErrorHandler();
            chmod($this->path, $this->accessMode);
            $this->resetErrorHandler();
        }
    }

    /**
     * Setup an error handler that throws an exception for every emitted E_WARNING
     */
    protected function setupErrorHandler()
    {
        set_error_handler(
            function ($errno, $errstr) {
                restore_error_handler(); // Should we call resetErrorHandler() here? (Requires it to be public)
                throw new Exception($errstr);
            },
            E_WARNING
        );
    }

    /**
     * Reset the error handler to the system default
     */
    protected function resetErrorHandler()
    {
        restore_error_handler();
    }
}
