<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Command\Transport;

use Icinga\Application\Hook\AuditHook;
use Icinga\Application\Logger;
use Icinga\Exception\Json\JsonDecodeException;
use Icinga\Module\Monitoring\Command\IcingaApiCommand;
use Icinga\Module\Monitoring\Command\IcingaCommand;
use Icinga\Module\Monitoring\Command\Renderer\IcingaApiCommandRenderer;
use Icinga\Module\Monitoring\Exception\CommandTransportException;
use Icinga\Module\Monitoring\Exception\CurlException;
use Icinga\Module\Monitoring\Web\Rest\RestRequest;
use Icinga\Util\Json;

/**
 * Command transport over Icinga 2's REST API
 */
class ApiCommandTransport implements CommandTransportInterface
{
    /**
     * Transport identifier
     */
    const TRANSPORT = 'api';

    /**
     * API host
     *
     * @var string
     */
    protected $host;

    /**
     * API password
     *
     * @var string
     */
    protected $password;

    /**
     * API port
     *
     * @var int
     */
    protected $port = 5665;

    /**
     * CA bundle path
     *
     * @var string
     */
    protected $caFile = null;

    /**
     * Whether to verify the hostname
     *
     * @var bool
     */
    protected $verifyHostname = true;

    /**
     * Command renderer
     *
     * @var IcingaApiCommandRenderer
     */
    protected $renderer;

    /**
     * API username
     *
     * @var string
     */
    protected $username;

    /**
     * API client key
     *
     * @var string
     */
    protected $clientKey;

    /**
     * API client certificate
     *
     * @var string
     */
    protected $clientCert;

    /**
     * Create a new API command transport
     */
    public function __construct()
    {
        $this->renderer = new IcingaApiCommandRenderer();
    }

    /**
     * Set the name of the Icinga application object
     *
     * @param   string  $app
     *
     * @return  $this
     */
    public function setApp($app)
    {
        $this->renderer->setApp($app);

        return $this;
    }

    /**
     * Get the API host
     *
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Set the API host
     *
     * @param   string  $host
     *
     * @return  $this
     */
    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * Get the API password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set the API password
     *
     * @param   string  $password
     *
     * @return  $this
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get the API port
     *
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Set the API port
     *
     * @param   int $port
     *
     * @return  $this
     */
    public function setPort($port)
    {
        $this->port = (int) $port;

        return $this;
    }

    /**
     * Get the CA file
     *
     * @return  string
     */
    public function getCaFile()
    {
        return $this->caFile;
    }

    /**
     * Set the CA file
     *
     * @param   string $caFile
     *
     * @return  $this
     */
    public function setCaFile($caFile)
    {
        $this->caFile = $caFile;

        return $this;
    }

    /**
     * Get whether hostnames should be verified
     *
     * @return  bool
     */
    public function getVerifyHostname()
    {
        return $this->verifyHostname;
    }

    /**
     * Set whether hostnames should be verified
     *
     * @param   string $verifyHostname
     *
     * @return  $this
     */
    public function setVerifyHostname($verifyHostname)
    {
        $this->verifyHostname = $verifyHostname;

        return $this;
    }

    /**
     * Get the API username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set the API username
     *
     * @param   string  $username
     *
     * @return  $this
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get the auth key path
     *
     * @return string
     */
    public function getClientKey()
    {
        return $this->clientKey;
    }

    /**
     * Set the auth key path
     *
     * @param   string  $clientKey
     *
     * @return  $this
     */
    public function setClientKey($clientKey)
    {
        $this->clientKey = $clientKey;

        return $this;
    }

    /**
     * Get the auth certificate path
     *
     * @return string
     */
    public function getClientCert()
    {
        return $this->clientCert;
    }

    /**
     * Set the auth certificate path
     *
     * @param   string  $clientCert
     *
     * @return  $this
     */
    public function setClientCert($clientCert)
    {
        $this->clientCert = $clientCert;

        return $this;
    }

    /**
     * Get URI for endpoint
     *
     * @param   string  $endpoint
     *
     * @return  string
     */
    protected function getUriFor($endpoint)
    {
        return sprintf('https://%s:%u/v1/%s', $this->getHost(), $this->getPort(), $endpoint);
    }

    protected function sendCommand(IcingaApiCommand $command)
    {
        Logger::debug(
            'Sending Icinga command "%s" to the API "%s:%u"',
            $command->getEndpoint(),
            $this->getHost(),
            $this->getPort()
        );

        $data = $command->getData();
        $payload = Json::encode($data);
        AuditHook::logActivity(
            'monitoring/command',
            "Issued command {$command->getEndpoint()} with the following payload: $payload",
            $data
        );

        try {
            $request = RestRequest::post($this->getUriFor($command->getEndpoint()))
                ->sendJson()
                ->setPayload($command->getData());

            if ($this->getCaFile() != null) {
                $request->setCaFile($this->getCaFile());
                $request->setVerifyHostname($this->getVerifyHostname());
            } else {
                $request->noStrictSsl();
            }

            if ($this->getClientKey() && $this->getClientCert()) {
                $request->authenticateCert($this->getClientKey(), $this->getClientCert());
            } else {
                $request->authenticateWith($this->getUsername(), $this->getPassword());
            }
            $response = $request->send();
        } catch (JsonDecodeException $e) {
            throw new CommandTransportException(
                'Got invalid JSON response from the Icinga 2 API: %s',
                $e->getMessage()
            );
        }

        if (isset($response['error'])) {
            throw new CommandTransportException(
                'Can\'t send external Icinga command: %u %s',
                $response['error'],
                $response['status']
            );
        }
        $result = array_pop($response['results']);
        if (! empty($result)
            && ($result['code'] < 200 || $result['code'] >= 300)
        ) {
            throw new CommandTransportException(
                'Can\'t send external Icinga command: %u %s',
                $result['code'],
                $result['status']
            );
        }
        if ($command->hasNext()) {
            $this->sendCommand($command->getNext());
        }
    }

    /**
     * Send the Icinga command over the Icinga 2 API
     *
     * @param   IcingaCommand   $command
     * @param   int|null        $now
     *
     * @throws  CommandTransportException
     */
    public function send(IcingaCommand $command, $now = null)
    {
        $this->sendCommand($this->renderer->render($command));
    }

    /**
     * Try to connect to the API
     *
     * @throws  CommandTransportException   In case of failure
     */
    public function probe()
    {
        $request = RestRequest::get($this->getUriFor(null));

        if ($this->getCaFile() != null) {
            $request->setCaFile($this->getCaFile());
            $request->setVerifyHostname($this->getVerifyHostname());
        } else {
            $request->noStrictSsl();
        }

        if ($this->getClientKey() && $this->getClientCert()) {
            $request->authenticateCert($this->getClientKey(), $this->getClientCert());
        } else {
            $request->authenticateWith($this->getUsername(), $this->getPassword());
        }

        try {
            $response = $request->send();
        } catch (CurlException $e) {
            throw new CommandTransportException(
                'Couldn\'t connect to the Icinga 2 API: %s',
                $e->getMessage()
            );
        } catch (JsonDecodeException $e) {
            throw new CommandTransportException(
                'Got invalid JSON response from the Icinga 2 API: %s',
                $e->getMessage()
            );
        }

        if (isset($response['error'])) {
            throw new CommandTransportException(
                'Can\'t connect to the Icinga 2 API: %u %s',
                $response['error'],
                $response['status']
            );
        }
    }
}
