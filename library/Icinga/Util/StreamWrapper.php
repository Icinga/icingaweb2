<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Util;

use Exception;
use Icinga\Data\StreamInterface;
use RuntimeException;

/**
 * Wrap a stream
 */
class StreamWrapper implements StreamInterface
{
    /**
     * The stream being wrapped
     *
     * @var resource
     */
    protected $handle;

    /**
     * StreamWrapper constructor
     *
     * @param   resource    $handle
     */
    public function __construct($handle)
    {
        $this->handle = $handle;
    }

    /**
     * @see {@link StreamInterface::__toString()}
     */
    public function __toString()
    {
        try {
            $this->rewind();
            return $this->getContents();
        } catch (Exception $e) {
            return (string) $e;
        }
    }

    /**
     * @see {@link StreamInterface::close()}
     */
    public function close()
    {
        $this->assertSuccessfulFunctionCall('fclose', array($this->handle));
    }

    /**
     * @see {@link StreamInterface::detach()}
     */
    public function detach()
    {
        $handle = $this->handle;
        $this->handle = null;
        return $handle;
    }

    /**
     * @see {@link StreamInterface::getSize()}
     */
    public function getSize()
    {
        $stats = $this->assertSuccessfulFunctionCall('fstat', array($this->handle));
        return $stats['size'];
    }

    /**
     * @see {@link StreamInterface::tell()}
     */
    public function tell()
    {
        return $this->assertSuccessfulFunctionCall('ftell', array($this->handle));
    }

    /**
     * @see {@link StreamInterface::eof()}
     */
    public function eof()
    {
        return feof($this->handle);
    }

    /**
     * @see {@link StreamInterface::isSeekable()}
     */
    public function isSeekable()
    {
        return $this->getMetadata('seekable');
    }

    /**
     * @see {@link StreamInterface::seek()}
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        $this->assertSuccessfulFunctionCall('fseek', array($this->handle, $offset, $whence), function ($value) {
            return $value !== -1;
        });
    }

    /**
     * @see {@link StreamInterface::rewind()}
     */
    public function rewind()
    {
        $this->assertSuccessfulFunctionCall('rewind', array($this->handle));
    }

    /**
     * @see {@link StreamInterface::isWritable()}
     */
    public function isWritable()
    {
        return ! preg_match('/^r(?!\+)/', $this->getMetadata('mode'));
    }

    /**
     * @see {@link StreamInterface::write()}
     */
    public function write($string)
    {
        return $this->assertSuccessfulFunctionCall('fwrite', array($this->handle, $string));
    }

    /**
     * @see {@link StreamInterface::isReadable()}
     */
    public function isReadable()
    {
        return preg_match('/[r|+]/', $this->getMetadata('mode'));
    }

    /**
     * @see {@link StreamInterface::read()}
     */
    public function read($length)
    {
        return $this->assertSuccessfulFunctionCall('fread', array($this->handle, $length));
    }

    /**
     * @see {@link StreamInterface::getContents()}
     */
    public function getContents()
    {
        return $this->assertSuccessfulFunctionCall('stream_get_contents', array($this->handle));
    }

    /**
     * @see {@link StreamInterface::getMetadata()}
     */
    public function getMetadata($key = null)
    {
        $result = stream_get_meta_data($this->handle);
        return isset($result[$key]) ? $result[$key] : $result;
    }

    /**
     * Pass the data stored in the buffer to the user agent (as with {@link fpassthru()})
     */
    public function fpassthru()
    {
        // fpassthru() returns the amount of bytes passed so we could
        // perform an error check, but throwing exceptions here makes
        // no sense as we're already writing the response body
        fpassthru($this->handle);
    }

    /**
     * Call a function as with {@link call_user_func_array()}
     *
     * @param   string      $functionName
     * @param   array       $args
     * @param   callable    $resultValidator
     *
     * @return  mixed
     *
     * @throws  RuntimeException    If the function call didn't succeed
     */
    protected function assertSuccessfulFunctionCall($functionName, array $args, $resultValidator = null)
    {
        $result = call_user_func_array($functionName, $args);
        if ($resultValidator === null ? $result === false : ! call_user_func($resultValidator, $result)) {
            throw new RuntimeException(
                'Error: ' . $functionName . '(' . implode(', ', array_map(array($this, 'represent'), $args))
                    . ') = ' . $this->represent($result)
            );
        }

        return $result;
    }

    /**
     * Return a value's string representation
     *
     * @param   mixed   $value
     *
     * @return  string
     */
    protected function represent($value)
    {
        return is_scalar($value) ? var_export($value, true) : print_r($value, true);
    }
}
