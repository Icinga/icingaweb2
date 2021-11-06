<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Web\Rest;

use Exception;
use Icinga\Application\Logger;
use Icinga\Util\Json;
use Icinga\Module\Monitoring\Exception\CurlException;

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
     * Whether to authenticate with cert auth
     *
     * @var bool
     */
    protected $hasCertAuth;

    /**
     * Cert auth key
     *
     * @var string
     */
    protected $authKey;

    /**
     * Cert auth certificate
     *
     * @var string
     */
    protected $authCert;

    /**
     * Request payload
     *
     * @var mixed
     */
    protected $payload;

    /**
     * Path to a CA certificate file, can be
     * set to null to not explicitly set one.
     *
     * @var string
     */
    protected $caFile = null;

    /**
     * Whether to verify the hostname when CA
     * verification is enabled.
     *
     * @var bool
     */
    protected $verifyHostname = true;

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
     * Create a GET REST request
     *
     * @param   string  $uri
     *
     * @return  static
     */
    public static function get($uri)
    {
        $request = new static;
        $request->uri = $uri;
        $request->method = 'GET';
        return $request;
    }

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
        $this->hasCertAuth = false;
        $this->hasBasicAuth = true;
        $this->username = $username;
        $this->password = $password;

        return $this;
    }

    /**
     * Set certificate auth credentials
     *
     * @param   string  $authKey
     * @param   string  $authCert
     *
     * @return  $this
     */
    public function authenticateCert($authKey, $authCert)
    {
        $this->hasCertAuth = true;
        $this->hasBasicAuth = false;
        $this->authKey = $authKey;
        $this->authCert = $authCert;

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
     * Set a CA file
     *
     * @return $this
     */
    public function setCaFile($caFile)
    {
        $this->caFile = $caFile;

        return $this;
    }

    /**
     * Enable or disable hostname verification
     *
     * @return $this
     */
    public function setVerifyHostname($verifyHostname)
    {
        $this->verifyHostname = $verifyHostname;

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
                $payload = Json::encode($payload);
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

        $options = array(
            CURLOPT_URL     => $this->uri,
            CURLOPT_TIMEOUT => $this->timeout,
            // Ignore proxy settings
            CURLOPT_PROXY           => '',
            CURLOPT_CUSTOMREQUEST   => $this->method
        );

        // Record cURL command line for debugging
        $curlCmd = array('curl', '-s', '-X', $this->method, '-H', escapeshellarg('Accept: application/json'));

        if ($this->strictSsl) {
            $options[CURLOPT_SSL_VERIFYHOST] = 2;
            $options[CURLOPT_SSL_VERIFYPEER] = $this->verifyHostname;
        } else {
            $options[CURLOPT_SSL_VERIFYHOST] = false;
            $options[CURLOPT_SSL_VERIFYPEER] = false;
            $curlCmd[] = '-k';
        }

        if ($this->caFile != null) {
            $options[CURLOPT_CAINFO] = $this->caFile;
            $curlCmd += [ '--cacert', escapeshellarg($this->caFile) ];
        }

        if ($this->hasBasicAuth) {
            $options[CURLOPT_USERPWD] = sprintf('%s:%s', $this->username, $this->password);
            $curlCmd[] = sprintf('-u %s:%s', escapeshellarg($this->username), escapeshellarg($this->password));
        }

        if ($this->hasCertAuth) {
            $options[CURLOPT_SSLKEY] = $this->authKey;
            $options[CURLOPT_SSLCERT] = $this->authCert;
            $curlCmd += [ '--key', escapeshellarg($this->authKey), '--cert', escapeshellarg($this->authCert) ];
        }

        if (! empty($this->payload)) {
            $payload = $this->serializePayload($this->payload, $this->contentType);
            $options[CURLOPT_POSTFIELDS] = $payload;
            $curlCmd[] = sprintf('-d %s', escapeshellarg($payload));
        }

        $options[CURLOPT_HTTPHEADER] = $headers;

        $stream = null;
        $logger = Logger::getInstance();
        if ($logger !== null && $logger->getLevel() === Logger::DEBUG) {
            $stream = fopen('php://temp', 'w');
            $options[CURLOPT_VERBOSE] = true;
            $options[CURLOPT_STDERR] = $stream;
        }

        Logger::debug(
            'Executing %s %s',
            implode(' ', $curlCmd),
            escapeshellarg($this->uri)
        );

        $result = $this->curlExec($options);

        if (is_resource($stream)) {
            rewind($stream);
            Logger::debug(stream_get_contents($stream));
            fclose($stream);
        }

        return Json::decode($result, true);
    }

    /**
     * Set up a new cURL handle with the given options and call {@link curl_exec()}
     *
     * @param   array   $options
     *
     * @return  string  The response
     *
     * @throws  CurlException
     */
    protected function curlExec(array $options)
    {
        $ch = curl_init();
        $options[CURLOPT_RETURNTRANSFER] = true;
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);

        if ($result === false) {
            throw new CurlException('%s', curl_error($ch));
        }

        curl_close($ch);
        return $result;
    }
}
