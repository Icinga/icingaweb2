<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Command;

class IcingaApiCommand
{
    /**
     * Command data
     *
     * @var array
     */
    protected $data;

    /**
     * Name of the endpoint
     *
     * @var string
     */
    protected $endpoint;

    /**
     * Next Icinga API command to be sent, if any
     *
     * @var static
     */
    protected $next;

    /**
     * Create a new Icinga 2 API command
     *
     * @param   string  $endpoint
     * @param   array   $data
     *
     * @return  static
     */
    public static function create($endpoint, array $data)
    {
        $command = new static();
        $command
            ->setEndpoint($endpoint)
            ->setData($data);
        return $command;
    }

    /**
     * Get the command data
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set the command data
     *
     * @param   array   $data
     *
     * @return  $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get the name of the endpoint
     *
     * @return string
     */
    public function getEndpoint()
    {
        return $this->endpoint;
    }

    /**
     * Set the name of the endpoint
     *
     * @param   string  $endpoint
     *
     * @return  $this
     */
    public function setEndpoint($endpoint)
    {
        $this->endpoint = $endpoint;

        return $this;
    }

    /**
     * Get whether another Icinga API command should be sent after this one
     *
     * @return bool
     */
    public function hasNext()
    {
        return $this->next !== null;
    }

    /**
     * Get the next Icinga API command
     *
     * @return IcingaApiCommand
     */
    public function getNext()
    {
        return $this->next;
    }

    /**
     * Set the next Icinga API command
     *
     * @param   IcingaApiCommand    $next
     *
     * @return  IcingaApiCommand
     */
    public function setNext(IcingaApiCommand $next)
    {
        $this->next = $next;
        return $next;
    }
}
