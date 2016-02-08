<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Data;

use Icinga\Application\Logger;
use Icinga\Exception\ProgrammingError;

/**
 * Contains information about an object in the form of human-readable log entries and indicates if the object has errors
 */
class Inspection
{
    /**
     * @var array
     */
    protected $log = array();

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string|Inspection
     */
    protected $error;

    /**
     * @param $description     Describes the object that is being inspected
     */
    public function __construct($description)
    {
        $this->description = $description;
    }

    /**
     * Get the name of this Inspection
     *
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Append the given log entry or nested inspection
     *
     * @throws  ProgrammingError            When called after erroring
     *
     * @param $entry    string|Inspection   A log entry or nested inspection
     */
    public function write($entry)
    {
        if (isset($this->error)) {
            throw new ProgrammingError('Inspection object used after error');
        }
        if ($entry instanceof Inspection) {
            $this->log[$entry->description] = $entry->toArray();
        } else {
            Logger::debug($entry);
            $this->log[] = $entry;
        }
    }

    /**
     * Append the given log entry and fail this inspection with the given error
     *
     * @param   $entry  string|Inspection   A log entry or nested inspection
     *
     * @throws  ProgrammingError            When called multiple times
     *
     * @return  this                        fluent interface
     */
    public function error($entry)
    {
        if (isset($this->error)) {
            throw new ProgrammingError('Inspection object used after error');
        }
        Logger::error($entry);
        $this->log[] = $entry;
        $this->error = $entry;
        return $this;
    }

    /**
     * If the inspection resulted in an error
     *
     * @return bool
     */
    public function hasError()
    {
        return isset($this->error);
    }

    /**
     * The error that caused the inspection to fail
     *
     * @return Inspection|string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Convert the inspection to an array
     *
     * @return array     An array of strings that describe the state in a human-readable form, each array element
     *                   represents one log entry about this object.
     */
    public function toArray()
    {
        return $this->log;
    }

    /**
     * Return a text representation of the inspection log entries
     */
    public function __toString()
    {
        return sprintf(
            'Inspection: description: "%s" error: "%s"',
            $this->description,
            $this->error
        );
    }
}
