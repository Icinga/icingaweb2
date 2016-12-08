<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Command\Transport;

use Icinga\Application\Logger;
use Icinga\Module\Monitoring\Command\IcingaApiCommand;
use Icinga\Module\Monitoring\Command\IcingaCommand;
use Icinga\Module\Monitoring\Command\Renderer\IcingaApiCommandRenderer;
use Icinga\Module\Monitoring\Exception\CommandTransportException;
use Icinga\Module\Monitoring\Web\Rest\RestRequest;

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
        $response = RestRequest::post($this->getUriFor($command->getEndpoint()))
            ->authenticateWith($this->getUsername(), $this->getPassword())
            ->sendJson()
            ->noStrictSsl()
            ->setPayload($command->getData())
            ->send();
        if (isset($response['error'])) {
            throw new CommandTransportException(
                'Can\'t send external Icinga command: %u %s',
                $response['error'],
                $response['status']
            );
        }
        $result = array_pop($response['results']);
        if ($result['code'] < 200 || $result['code'] >= 300) {
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
}
