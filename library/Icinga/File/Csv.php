<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\File;

use http\Exception\InvalidArgumentException;
use Icinga\Module\Monitoring\Backend\Ido\Query\IdoQuery;
use Traversable;

class Csv
{
    /**
     * @var IdoQuery
     */
    protected $query;

    /**
     * @var \DateTimeZone
     */
    protected $timezone;



    protected function __construct()
    {
    }

    /**
     * @param Traversable $query
     * @param null $timezone - using null will output unix timestamps
     * @return Csv
     */
    public static function fromQuery(Traversable $query, $timezone = null)
    {
        $csv = new static();
        $csv->query = $query;
        $csv->setTimezone($timezone);

        return $csv;
    }

    public function dump()
    {
        if ("cli" !== php_sapi_name()) {
            header('Content-type: text/csv');
        }
        echo (string) $this;
    }

    public function __toString()
    {
        $first = true;
        $csv = '';

        //timestamp strings won't be modified if $timestampColumns is empty
        $timestampColumns = (null === $this->timezone) ?
            [] :
            $this->query->getTimestampColumns()
        ;

        foreach ($this->query as $row) {
            if ($first) {
                $csv .= implode(',', array_keys((array)$row)) . "\r\n";
                $first = false;
            }
            $out = [];
            foreach ($row as $columnName => $val) {
                $val = in_array($columnName, $timestampColumns) ?
                    \DateTime::createFromFormat('U', $val)->setTimezone($this->timezone)->format(\DateTime::ISO8601) :
                    $val;

                $out[] = '"' . $val . '"';
            }
            $csv .= implode(',', $out) . "\r\n";
        }

        return $csv;
    }

    /**
     * @param string|null $timezoneString
     * @throws \InvalidArgumentException
     */
    public function setTimezone($timezoneString = null)
    {
        if (null === $timezoneString) {
            $this->timezone = null;
        } elseif (is_string($timezoneString) && in_array($timezoneString, \DateTimeZone::listIdentifiers())) {
            $this->timezone = new \DateTimeZone($timezoneString);
        } else {
            throw new InvalidArgumentException("$timezoneString is not a valid timezone identifier.");
        }
    }
}
