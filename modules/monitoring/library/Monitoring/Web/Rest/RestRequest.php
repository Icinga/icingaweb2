<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Web\Rest;

use Exception;
use Icinga\Application\Logger;
use Icinga\Module\Monitoring\Util\TemporaryDirectory;
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
     * Whether to verify the remote TLS certificate's CN
     *
     * @var bool
     */
    protected $strictCn = true;

    /**
     * Request timeout
     *
     * @var int
     */
    protected $timeout = 30;

    /**
     * Our TLS client certificate file
     *
     * @var string|null
     */
    protected $tlsClientCert;

    /**
     * Our TLS private key file
     *
     * @var string|null
     */
    protected $tlsClientKey;

    /**
     * A file with the server's TLS certificate or the issuer CA
     *
     * @var string|null
     */
    protected $tlsServerCert;

    /**
     * A temporary directory for TLS certificates and private keys
     *
     * @var TemporaryDirectory|null
     */
    protected $tempDir;

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
     * Disable strict TLS CN
     *
     * @return $this
     */
    public function noStrictTlsCn()
    {
        $this->strictCn = false;

        return $this;
    }

    /**
     * Set our TLS certificate and private key
     *
     * @param   string  $cert   Certificate, either PEM or an absolute path to a PEM file
     * @param   string  $key    Private key, either PEM or an absolute path to a PEM file
     *
     * @return  $this
     */
    public function setTlsClient($cert, $key)
    {
        if (substr($cert, 0, 1) === '-') {
            $certPath = $this->getTempDir() . DIRECTORY_SEPARATOR . 'client.crt';
            file_put_contents($certPath, $cert);
            $cert = $certPath;
        }

        if (substr($key, 0, 1) === '-') {
            $keyPath = $this->getTempDir() . DIRECTORY_SEPARATOR . 'client.key';
            file_put_contents($keyPath, $key);
            $key = $keyPath;
        }

        $this->tlsClientCert = $cert;
        $this->tlsClientKey = $key;

        return $this;
    }

    /**
     * Set the remote's TLS certificate or the issuer CA
     *
     * @param   string  $cert   Certificate, either PEM or an absolute path to a PEM file
     *
     * @return  $this
     */
    public function setTlsServer($cert)
    {
        if (substr($cert, 0, 1) === '-') {
            $certPath = $this->getTempDir() . DIRECTORY_SEPARATOR . 'server.crt';
            file_put_contents($certPath, $cert);
            $cert = $certPath;
        }

        $this->tlsServerCert = $cert;

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
            $options[CURLOPT_SSL_VERIFYHOST] = $this->strictCn ? 2 : 0;
            $options[CURLOPT_SSL_VERIFYPEER] = true;

            if ($this->tlsServerCert !== null) {
                $options[CURLOPT_CAINFO] = $this->tlsServerCert;
            }
        } else {
            $options[CURLOPT_SSL_VERIFYHOST] = false;
            $options[CURLOPT_SSL_VERIFYPEER] = false;
            $curlCmd[] = '-k';
        }

        if ($this->tlsClientCert !== null) {
            $options[CURLOPT_SSLCERT] = $this->tlsClientCert;
            $options[CURLOPT_SSLKEY] = $this->tlsClientKey;
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

    /**
     * Get {@link tempDir}
     *
     * @return TemporaryDirectory
     */
    protected function getTempDir()
    {
        if ($this->tempDir === null) {
            $this->tempDir = new TemporaryDirectory();
        }

        return $this->tempDir;
    }
}
