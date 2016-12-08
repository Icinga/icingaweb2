<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Timeline;

use DateTime;
use Icinga\Web\Url;
use Icinga\Exception\ProgrammingError;

/**
 * An event group that is part of a timeline
 */
class TimeEntry
{
    /**
     * The name of this group
     *
     * @var string
     */
    protected $name;

    /**
     * The amount of events that are part of this group
     *
     * @var int
     */
    protected $value;

    /**
     * The date and time of this group
     *
     * @var DateTime
     */
    protected $dateTime;

    /**
     * The url to this group's detail view
     *
     * @var Url
     */
    protected $detailUrl;

    /**
     * The weight of this group
     *
     * @var float
     */
    protected $weight = 1.0;

    /**
     * The label of this group
     *
     * @var string
     */
    protected $label;

    /**
     * The CSS class of the entry
     *
     * @var string
     */
    protected $class;

    /**
     * Return a new TimeEntry object with the given attributes being set
     *
     * @param   array       $attributes     The attributes to set
     * @return  TimeEntry                   The resulting TimeEntry object
     * @throws  ProgrammingError            If one of the given attributes cannot be set
     */
    public static function fromArray(array $attributes)
    {
        $entry = new TimeEntry();

        foreach ($attributes as $name => $value) {
            $methodName = 'set' . ucfirst($name);
            if (method_exists($entry, $methodName)) {
                $entry->{$methodName}($value);
            } else {
                throw new ProgrammingError(
                    'Method "%s" does not exist on object of type "%s"',
                    $methodName,
                    __CLASS__
                );
            }
        }

        return $entry;
    }

    /**
     * Set this group's name
     *
     * @param   string  $name   The name to set
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Return the name of this group
     *
     * @return  string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set this group's amount of events
     *
     * @param   int     $value  The value to set
     */
    public function setValue($value)
    {
        $this->value = intval($value);
    }

    /**
     * Return the amount of events in this group
     *
     * @return  int
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set this group's date and time
     *
     * @param   DateTime    $dateTime   The date and time to set
     */
    public function setDateTime(DateTime $dateTime)
    {
        $this->dateTime = $dateTime;
    }

    /**
     * Return the date and time of this group
     *
     * @return  DateTime
     */
    public function getDateTime()
    {
        return $this->dateTime;
    }

    /**
     * Set the url to this group's detail view
     *
     * @param   Url     $detailUrl      The url to set
     */
    public function setDetailUrl(Url $detailUrl)
    {
        $this->detailUrl = $detailUrl;
    }

    /**
     * Return the url to this group's detail view
     *
     * @return  Url
     */
    public function getDetailUrl()
    {
        return $this->detailUrl;
    }

    /**
     * Set this group's weight
     *
     * @param   float   $weight     The weight for this group
     */
    public function setWeight($weight)
    {
        $this->weight = floatval($weight);
    }

    /**
     * Return the weight of this group
     *
     * @return  float
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Set this group's label
     *
     * @param   string  $label   The label to set
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * Return the label of this group
     *
     * @return  string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Get the CSS class
     *
     * @return  string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Set the CSS class
     *
     * @param   string  $class
     *
     * @return  $this
     */
    public function setClass($class)
    {
        $this->class = $class;
        return $this;
    }
}
