<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\View;

use DateTime;

class DateTimeRenderer
{
    const TYPE_DATETIME = 0;
    const TYPE_TIME = 1;
    const TYPE_TIMESPAN = 2;

    const HOUR = 3600;

    /**
     * The present DateTime
     *
     * @var DateTime
     */
    protected $now;

    /**
     * The DateTime tense
     *
     * @var bool
     */
    protected $future;

    /**
     * The given DateTime type
     *
     * @var integer
     */
    protected $type;

    /**
     * The given DateTime
     *
     * @var DateTime
     */
    protected $dateTime;

    public function __construct($dateTime, $future = false)
    {
        $this->future = $future;
        $this->now = new DateTime();
        $this->dateTime = $this->normalize($dateTime);
        $this->detectType();
    }

    /**
     * Creates a new DateTimeRenderer
     *
     * @param DateTime|int $dateTime
     * @param bool $future
     *
     * @return DateTimeRenderer
     */
    public static function create($dateTime, $future = false)
    {
        return new static($dateTime, $future);
    }

    /**
     * Detects the DateTime context
     */
    protected function detectType()
    {
        if ($this->now->format('Y-m-d') !== $this->dateTime->format('Y-m-d')) {
            $this->type = self::TYPE_DATETIME;
            return;
        }

        if (
            $this->now->format('Y-m-d') === $this->dateTime->format('Y-m-d') &&
            (abs($this->now->getTimestamp() - $this->dateTime->getTimestamp()) >= self::HOUR)
        ) {
            $this->type = self::TYPE_TIME;
            return;
        }

        if (
            $this->now->format('Y-m-d') === $this->dateTime->format('Y-m-d') &&
            (abs($this->now->getTimestamp() - $this->dateTime->getTimestamp()) < self::HOUR)
        ) {
            $this->type = self::TYPE_TIMESPAN;
            return;
        }
    }

    /**
     * Normalizes the given DateTime
     *
     * @param DateTime|int $dateTime
     *
     * @return DateTime
     */
    public static function normalize($dateTime)
    {
        if (! ($dt = $dateTime) instanceof DateTime) {
            $dt = new DateTime();
            $dt->setTimestamp($dateTime);
        }
        return $dt;
    }

    /**
     * Checks whether DateTime is a date with time
     *
     * @return bool
     */
    public function isDateTime()
    {
        return $this->type === self::TYPE_DATETIME;
    }

    /**
     * Checks whether DateTime is a time of the current day
     *
     * @return bool
     */
    public function isTime()
    {
        return $this->type === self::TYPE_TIME;
    }

    /**
     * Checks whether DateTime is in a defined interval
     *
     * @return bool
     */
    public function isTimespan()
    {
        return $this->type === self::TYPE_TIMESPAN;
    }

    /**
     * Returns the type of the DateTime
     *
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Renders the DateTime on the basis of the type and returns suited text
     *
     * @param string $dateTimeText
     * @param string $timeText
     * @param string $timespanText
     *
     * @return string
     */
    public function render($dateTimeText, $timeText, $timespanText)
    {
        if ($this->isDateTime()) {
            return sprintf($dateTimeText, $this);
        } elseif ($this->isTime()) {
            return sprintf($timeText, $this);
        } elseif ($this->isTimespan()) {
            return sprintf($timespanText, $this);
        }

        return $dateTimeText;
    }

    /**
     * Returns a rendered html wrapped text
     *
     * @return string
     */
    public function __toString()
    {
        switch ($this->type) {
            case self::TYPE_DATETIME:
                $format = $this->dateTime->format('d.m.Y - H:i:s');
                break;
            case self::TYPE_TIME:
                $format = $this->dateTime->format('H:i:s');
                break;
            case self::TYPE_TIMESPAN:
                $format = $this->dateTime->diff($this->now)->format(t('%im %ss', 'timespan'));
                break;
            default:
                $format = $this->dateTime->format('d.m.Y - H:i:s');
                break;
        }

        $css = '';
        if ($this->type === self::TYPE_TIMESPAN) {
            $css = $this->future === true ? 'timeuntil' : 'timesince';
        }

        return sprintf(
            '<span class="%s" title="%s">%s</span>',
            $css,
            $this->dateTime->format('d.m.Y - H:i:s'),
            $format
        );
    }
}
