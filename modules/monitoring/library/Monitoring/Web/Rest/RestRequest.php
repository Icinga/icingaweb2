<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Web\Rest;

use Exception;
use Icinga\Application\Logger;

/**
 * REST Request
 */
class RestRequest
{
    /**
     * Request URI
     *
     * @var string
     */
    protected $uri;

    /**
     * Request method
     *
     * @var string
     */
    protected $method;

    /**
     * Request content type
     *
     * @var string
     */
    protected $contentType;

    /**
     * Whether to authenticate with basic auth
     *
     * @var bool
     */
    protected $hasBasicAuth;

    /**
     * Auth username
     *
     * @var string
     */
    protected $username;

    /**
     * Auth password
     *
     * @var string
     */
    protected $password;

    /**
     * Request payload
     *
     * @var mixed
     */
    protected $payload;

    /**
     * Whether strict SSL is enabled
     *
     * @var bool
     */
    protected $strictSsl = true;

    /**
     * Request timeout
     *
     * @var int
     */
    protected $timeout = 30;

    /**
     * Create a POST REST request
     *
     * @param   string  $uri
     *
     * @return  static
     */
    public static function post($uri)
    {
        $request = new static;
        $request->uri = $uri;
        $request->method = 'POST';
        return $request;
    }

    /**
     * Send content type JSON
     *
     * @return $this
     */
    public function sendJson()
    {
        $this->contentType = 'application/json';

        return $this;
    }

    /**
     * Set basic auth credentials
     *
     * @param   string  $username
     * @param   string  $password
     *
     * @return  $this
     */
    public function authenticateWith($username, $password)
    {
        $this->hasBasicAuth = true;
        $this->username = $username;
        $this->password = $password;

        return $this;
    }

    /**
     * Set request payload
     *
     * @param   mixed   $payload
     *
     * @return  $this
     */
    public function setPayload($payload)
    {
        $this->payload = $payload;

        return $this;
    }

    /**
     * Disable strict SSL
     *
     * @return $this
     */
    public function noStrictSsl()
    {
        $this->strictSsl = false;

        return $this;
    }

    /**
     * Serialize payload according to content type
     *
     * @param   mixed   $payload
     * @param   string  $contentType
     *
     * @return  string
     */
    public function serializePayload($payload, $contentType)
    {
        switch ($contentType) {
            case 'application/json':
                $payload = json_encode($payload);
                break;
        }

        return $payload;
    }

    /**
     * Send the request
     *
     * @return  mixed
     *
     * @throws  Exception
     */
    public function send()
    {
        $defaults = array(
            'host'  => 'localhost',
            'path'  => '/'
        );

        $url = array_merge($defaults, parse_url($this->uri));

        if (isset($url['port'])) {
            $url['host'] .= sprintf(':%u', $url['port']);
        }

        if (isset($url['query'])) {
            $url['path'] .= sprintf('?%s', $url['query']);
        }

        $headers = array(
            "{$this->method} {$url['path']} HTTP/1.1",
            "Host: {$url['host']}",
            "Content-Type: {$this->contentType}",
            'Accept: application/json',
            // Bypass "Expect: 100-continue" timeouts
            'Expect:'
        );

        $ch = curl_init();

        $options = array(
            CURLOPT_URL     => $this->uri,
            CURLOPT_TIMEOUT => $this->timeout,
            // Ignore proxy settings
            CURLOPT_PROXY           => '',
            CURLOPT_CUSTOMREQUEST   => $this->method,
            CURLOPT_RETURNTRANSFER  => true
        );

        // Record cURL command line for debugging
        $curlCmd = array('curl', '-s', '-X', $this->method, '-H', escapeshellarg('Accept: application/json'));

        if ($this->strictSsl) {
            $options[CURLOPT_SSL_VERIFYHOST] = 2;
            $options[CURLOPT_SSL_VERIFYPEER] = true;
        } else {
            $options[CURLOPT_SSL_VERIFYHOST] = false;
            $options[CURLOPT_SSL_VERIFYPEER] = false;
            $curlCmd[] = '-k';
        }

        if ($this->hasBasicAuth) {
            $options[CURLOPT_USERPWD] = sprintf('%s:%s', $this->username, $this->password);
            $curlCmd[] = sprintf('-u %s:%s', escapeshellarg($this->username), escapeshellarg($this->password));
        }

        if (! empty($this->payload)) {
            $payload = $this->serializePayload($this->payload, $this->contentType);
            $options[CURLOPT_POSTFIELDS] = $payload;
            $curlCmd[] = sprintf('-d %s', escapeshellarg($payload));
        }

        $options[CURLOPT_HTTPHEADER] = $headers;

        $stream = null;
        if (Logger::getInstance()->getLevel() === Logger::DEBUG) {
            $stream = fopen('php://temp', 'w');
            $options[CURLOPT_VERBOSE] = true;
            $options[CURLOPT_STDERR] = $stream;
        }

        curl_setopt_array($ch, $options);

        Logger::debug(
            'Executing %s %s',
            implode(' ', $curlCmd),
            escapeshellarg($this->uri)
        );

        $result = curl_exec($ch);

        if ($result === false) {
            throw new Exception(curl_error($ch));
        }

        curl_close($ch);

        if (is_resource($stream)) {
            rewind($stream);
            Logger::debug(stream_get_contents($stream));
            fclose($stream);
        }

        $response = @json_decode($result, true);

        if ($response === null) {
            if (version_compare(PHP_VERSION, '5.5.0', '>=')) {
                throw new Exception(json_last_error_msg());
            } else {
                switch (json_last_error()) {
                    case JSON_ERROR_DEPTH:
                        $msg = 'The maximum stack depth has been exceeded';
                        break;
                    case JSON_ERROR_CTRL_CHAR:
                        $msg = 'Control character error, possibly incorrectly encoded';
                        break;
                    case JSON_ERROR_STATE_MISMATCH:
                        $msg = 'Invalid or malformed JSON';
                        break;
                    case JSON_ERROR_SYNTAX:
                        $msg = 'Syntax error';
                        break;
                    case JSON_ERROR_UTF8:
                        $msg = 'Malformed UTF-8 characters, possibly incorrectly encoded';
                        break;
                    default:
                        $msg = 'An error occured when parsing a JSON string';
                }
                throw new Exception($msg);
            }
        }

        return $response;
    }
}
