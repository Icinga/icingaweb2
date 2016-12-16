<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Util;

use Icinga\Exception\IcingaException;

/**
 * Stores data in memory or a temporary file not to get out of memory
 */
class Buffer
{
    /**
     * The actual buffer
     *
     * @var resource
     */
    protected $handle;

    /**
     * The amount of bytes currently stored in the buffer
     *
     * @var int
     */
    protected $size = 0;

    /**
     * Whether the current position of {@link handle} is the end of file
     *
     * @var bool
     */
    protected $atEOF = true;

    /**
     * Buffer constructor
     */
    public function __construct()
    {
        $this->handle = fopen('php://temp', 'w+b');
    }

    /**
     * Append the given data to the buffer
     *
     * @param   string  $data
     */
    public function append($data)
    {
        $strlen = strlen($data);
        if ($strlen) {
            $this->seekToEnd();
            $this->fwrite($this->handle, $data);
            $this->size += $strlen;
        }
    }

    /**
     * Get size
     *
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Get the data stored in the buffer
     *
     * @return  string
     *
     * @throws  IcingaException     In case of an error
     */
    public function __toString()
    {
        $result = '';

        if ($this->size) {
            $this->seekToBegin();
            for (;;) {
                $buf = fread($this->handle, $this->size);
                if ($buf === '') {
                    break;
                }
                $result .= $buf;
            }

            if (strlen($result) !== $this->size) {
                throw new IcingaException('Couldn\'t read all data from the buffer');
            }
        }

        return $result;
    }

    /**
     * Pass the data stored in the buffer to the user agent (as with {@link fpassthru()})
     */
    public function fpassthru()
    {
        if ($this->size) {
            $this->seekToBegin();
            // fpassthru() returns the amount of bytes passed so we could perform
            // the same error check as in __toString(), but throwing exceptions here
            // makes no sense as we're already writing the response body
            fpassthru($this->handle);
        }
    }

    /**
     * fseek({@link handle}) to the begin of the buffer if not already done
     */
    protected function seekToBegin()
    {
        if ($this->atEOF) {
            if ($this->size) {
                $this->fseek($this->handle, 0);
            }
            $this->atEOF = false;
        }
    }

    /**
     * fseek({@link handle}) to the end of the buffer if not already done
     */
    protected function seekToEnd()
    {
        if (! $this->atEOF) {
            if ($this->size) {
                $this->fseek($this->handle, 0, SEEK_END);
            }
            $this->atEOF = true;
        }
    }

    /**
     * Forwards all passed parameters to {@link fseek()}
     *
     * @throws  IcingaException     In case of an error
     */
    protected function fseek()
    {
        if (call_user_func_array('fseek', func_get_args()) === -1) {
            $this->reportFunctionCallError('fseek', func_get_args());
        }
    }

    /**
     * Forwards all passed parameters to {@link fwrite()}
     *
     * @throws  IcingaException     In case of an error
     */
    protected function fwrite()
    {
        if (call_user_func_array('fwrite', func_get_args()) === 0) {
            $this->reportFunctionCallError('fwrite', func_get_args());
        }
    }

    /**
     * Throw an exception telling that the given function call didn't succeed
     *
     * @param   string  $functionName
     * @param   array   $passedArgs
     *
     * @throws  IcingaException     unconditional
     */
    protected function reportFunctionCallError($functionName, array $passedArgs)
    {
        $args = array();
        foreach ($passedArgs as $arg) {
            $args[] = print_r($arg, true);
        }
        throw new IcingaException('Couldn\'t ' . $functionName . '(' . implode(', ', $args) . ')');
    }
}
