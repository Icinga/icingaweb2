<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Protocol\Http;

use Icinga\Data\ConfigObject;
use Icinga\Exception\ConfigurationError;
use Icinga\Web\Form\Validator\HttpUrlValidator;
use iplx\Http\Client;
use iplx\Http\ClientInterface;
use iplx\Http\Uri;
use Psr\Http\Message\RequestInterface;

/**
 * Encapsulate HTTP(S) connections
 */
class HttpConnection implements ClientInterface
{
    /**
     * The resource configuration
     *
     * @var ConfigObject
     */
    protected $resourceConfig;

    /**
     * This object does the actual work
     *
     * @var ClientInterface
     */
    protected $encapsulatedClient;

    /**
     * Constructor
     *
     * @param   ConfigObject    $resourceConfig     The resource configuration
     *
     * @throws  ConfigurationError
     */
    public function __construct(ConfigObject $resourceConfig)
    {
        if (! isset($resourceConfig->baseurl)) {
            throw new ConfigurationError('Base URL missing');
        }

        $urlValidator = new HttpUrlValidator();
        if (! $urlValidator->isValid($resourceConfig->baseurl)) {
            throw new ConfigurationError('Bad base URL');
        }

        $this->resourceConfig = $resourceConfig;
    }

    public function send(RequestInterface $request, array $options = array())
    {
        $baseUri = new Uri($this->resourceConfig->baseurl);
        $uri = $request->getUri();

        return $this->getEncapsulatedClient()->send(
            $request->withUri(
                $uri->withScheme($baseUri->getScheme())
                    ->withHost($baseUri->getHost())
                    ->withPort($baseUri->getPort())
                    ->withPath(rtrim((string) $baseUri->getPath(), '/') . '/' . ltrim((string) $uri->getPath(), '/'))
            ),
            $options
        );
    }

    /**
     * Get the encapsulated HTTP client
     *
     * @return ClientInterface
     */
    public function getEncapsulatedClient()
    {
        if ($this->encapsulatedClient === null) {
            $this->encapsulatedClient = new Client();
        }

        return $this->encapsulatedClient;
    }

    /**
     * Set the encapsulated HTTP client
     *
     * @param ClientInterface $encapsulatedClient
     *
     * @return $this
     */
    public function setEncapsulatedClient(ClientInterface $encapsulatedClient)
    {
        $this->encapsulatedClient = $encapsulatedClient;

        return $this;
    }
}
