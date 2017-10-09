<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Web\Rest;

use Exception;
use Icinga\Module\Monitoring\Exception\CurlException;

/**
 * Wrap a cURL handle for persistent connections
 */
class Curl
{
    /**
     * The wrapped handle
     *
     * @var resource
     */
    protected $handle;

    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Set the handle's options as given, perform a session and return the result
     *
     * @param   array   $options
     *
     * @return  string
     *
     * @throws  CurlException
     */
    public function exec(array $options)
    {
        if ($this->handle === null) {
            $this->handle = curl_init();
        }

        try {
            $options[CURLOPT_RETURNTRANSFER] = true;
            curl_reset($this->handle);
            curl_setopt_array($this->handle, $options);
            $result = curl_exec($this->handle);

            if ($result === false) {
                throw new CurlException('%s', curl_error($this->handle));
            }
        } catch (Exception $e) {
            curl_close($this->handle);
            $this->handle = null;

            throw $e;
        }

        return $result;
    }

    /**
     * Destructor
     *
     * Theoretically this isn't needed as the OS cleans up all TCP/IP connections automatically,
     * but explicit is better than implicit.
     */
    public function __destruct()
    {
        if ($this->handle !== null) {
            curl_close($this->handle);
        }
    }
}
