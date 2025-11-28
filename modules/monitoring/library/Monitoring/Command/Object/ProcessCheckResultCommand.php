<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Command\Object;

use InvalidArgumentException;
use LogicException;

/**
 * Submit a passive check result for a host or service
 */
class ProcessCheckResultCommand extends ObjectCommand
{
    /**
     * (non-PHPDoc)
     * @see \Icinga\Module\Monitoring\Command\Object\ObjectCommand::$allowedObjects For the property documentation.
     */
    protected $allowedObjects = array(
        self::TYPE_HOST,
        self::TYPE_SERVICE
    );

    /**
     * Host up
     */
    const HOST_UP = 0;

    /**
     * Host down
     */
    const HOST_DOWN = 1;

    /**
     * Host unreachable
     */
    const HOST_UNREACHABLE = 2; // TODO: Icinga 2.x does not support submitting results with this state, yet

    /**
     * Service ok
     */
    const SERVICE_OK = 0;

    /**
     * Service warning
     */
    const SERVICE_WARNING = 1;

    /**
     * Service critical
     */
    const SERVICE_CRITICAL = 2;

    /**
     * Service unknown
     */
    const SERVICE_UNKNOWN = 3;

    /**
     * Possible status codes for passive host and service checks
     *
     * @var array
     */
    public static $statusCodes = array(
        self::TYPE_HOST => array(
            self::HOST_UP, self::HOST_DOWN, self::HOST_UNREACHABLE
        ),
        self::TYPE_SERVICE => array(
            self::SERVICE_OK, self::SERVICE_WARNING, self::SERVICE_CRITICAL, self::SERVICE_UNKNOWN
        )
    );

    /**
     * Status code of the host or service check result
     *
     * @var int
     */
    protected $status;

    /**
     * Text output of the host or service check result
     *
     * @var string
     */
    protected $output;

    /**
     * Optional performance data of the host or service check result
     *
     * @var string
     */
    protected $performanceData;


    /**
     * Set the status code of the host or service check result
     *
     * @param   int $status
     *
     * @return  $this
     *
     * @throws  LogicException              If the object is null
     * @throws  InvalidArgumentException    If status is not one of the valid status codes for the object's type
     */
    public function setStatus($status)
    {
        if ($this->object === null) {
            throw new LogicException('You\'re required to call setObject() before calling setStatus()');
        }
        $status = (int) $status;
        if (! in_array($status, self::$statusCodes[$this->object->getType()])) {
            throw new InvalidArgumentException(sprintf(
                'The status code %u you provided is not one of the valid status codes for type %s',
                $status,
                $this->object->getType()
            ));
        }
        $this->status = $status;
        return $this;
    }

    /**
     * Get the status code of the host or service check result
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set the text output of the host or service check result
     *
     * @param   string $output
     *
     * @return  $this
     */
    public function setOutput($output)
    {
        $this->output = (string) $output;
        return $this;
    }

    /**
     * Get the text output of the host or service check result
     *
     * @return string
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Set the performance data of the host or service check result
     *
     * @param   string $performanceData
     *
     * @return  $this
     */
    public function setPerformanceData($performanceData)
    {
        $this->performanceData = (string) $performanceData;
        return $this;
    }

    /**
     * Get the performance data of the host or service check result
     *
     * @return string
     */
    public function getPerformanceData()
    {
        return $this->performanceData;
    }
}
