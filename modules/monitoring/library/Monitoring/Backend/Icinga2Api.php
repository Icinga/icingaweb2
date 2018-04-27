<?php
/* Icinga Web 2 | (c) 2018 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend;

use Icinga\Data\Filter\Filter;
use Icinga\Data\Filter\FilterChain;
use Icinga\Data\Filter\FilterExpression;
use Icinga\Data\Filter\FilterNot;
use Icinga\Data\Filter\FilterOr;
use Icinga\Exception\ConfigurationError;
use Icinga\Module\Monitoring\Command\Transport\ApiCommandTransport;
use Icinga\Module\Monitoring\Command\Transport\CommandTransport;
use Icinga\Module\Monitoring\Web\Rest\RestRequest;
use Icinga\Web\Url;
use Icinga\Web\UrlParams;
use stdClass;

/**
 * OOP abstraction for the Icinga 2 API
 */
class Icinga2Api
{
    /**
     * API host
     *
     * @var string
     */
    protected $host;

    /**
     * API port
     *
     * @var int
     */
    protected $port = 5665;

    /**
     * API username
     *
     * @var string
     */
    protected $username;

    /**
     * API password
     *
     * @var string
     */
    protected $password;

    /**
     * Create from the first configured command transport
     *
     * @return  static
     *
     * @throws  ConfigurationError  If no Icinga 2 API command transports is configured
     */
    public static function fromTransport()
    {
        foreach (CommandTransport::getConfig() as $name => $transportConfig) {
            $transport = CommandTransport::createTransport($transportConfig);

            if ($transport instanceof ApiCommandTransport) {
                $api = new static();
                return $api
                    ->setHost($transport->getHost())
                    ->setPort($transport->getPort())
                    ->setUsername($transport->getUsername())
                    ->setPassword($transport->getPassword());
            }
        }

        throw new ConfigurationError('No Icinga 2 API command transports configured');
    }

    /**
     * Queries objects
     *
     * @param   string  $type   e.g. 'hosts', 'services'
     * @param   Filter  $filter
     *
     * @return  array   The response (JSON decoded)
     *
     * @see     https://www.icinga.com/docs/icinga2/latest/doc/12-icinga2-api/#querying-objects
     */
    public function objects($type, Filter $filter = null)
    {
        return $this->get("objects/$type", null, $filter === null ? null : $this->filterToJson($filter))->send();
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
     * Assemble a GET request to the API
     *
     * @param   string      $relativeUrl    e.g. 'objects/hosts'
     * @param   UrlParams   $params
     * @param   stdClass    $payload        Will be JSON encoded
     *
     * @return  RestRequest
     */
    protected function get($relativeUrl, UrlParams $params = null, stdClass $payload = null)
    {
        $url = Url::fromPath("https://$this->host:$this->port/v1/$relativeUrl");

        if ($params !== null) {
            $url->setParams($params);
        }

        $request = RestRequest::get($url->getAbsoluteUrl())
            ->noStrictSsl()
            ->authenticateWith($this->username, $this->password);

        if ($payload !== null) {
            $request->sendJson()
                ->setPayload($payload);
        }

        return $request;
    }

    /**
     * Convert the given filter to a data structure suitable for JSON encoding
     *
     * @param   Filter  $filter
     *
     * @return  stdClass
     */
    protected function filterToJson(Filter $filter)
    {
        $vars = array();
        $rendered = $this->renderSubFilter($filter, $vars);

        return (object) array(
            'filter'        => $rendered,
            'filter_vars'   => (object) $vars
        );
    }

    /**
     * Render the given filter and store the filter_vars in the given array
     *
     * @param   Filter  $filter
     * @param   array   $vars
     *
     * @return  string
     */
    protected function renderSubFilter(Filter $filter, array &$vars)
    {
        if ($filter instanceof FilterExpression) {
            $nextVar = 'fv' . count($vars);
            $vars[$nextVar] = $filter->getExpression();
            $sign = $filter->getSign();

            if ($sign === '=') {
                $sign = '==';
            }

            return "{$filter->getColumn()} $sign $nextVar";
        }

        if ($filter instanceof FilterChain) {
            $filters = $filter->filters();

            if ($filter instanceof FilterNot) {
                return "! ({$this->renderSubFilter($filters[0], $vars)})";
            }

            $rendered = array();
            foreach ($filters as $subFilter) {
                $rendered[] = $this->renderSubFilter($subFilter, $vars);
            }

            return '(' . join($filter instanceof FilterOr ? ') || (' : ') && (', $rendered) . ')';
        }
    }
}
