<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Web;

/**
 * An announcement to be displayed prominently in the web UI
 */
class Announcement
{
    /**
     * @var string
     */
    protected $author;

    /**
     * @var string
     */
    protected $message;

    /**
     * @var int
     */
    protected $start;

    /**
     * @var int
     */
    protected $end;

    /**
     * Hash of the message
     *
     * @var string|null
     */
    protected $hash = null;

    /**
     * Announcement constructor
     *
     * @param array $properties
     */
    public function __construct(array $properties = array())
    {
        foreach ($properties as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
    }

    /**
     * Get the author of the acknowledged
     *
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set the author of the acknowledged
     *
     * @param   string $author
     *
     * @return  $this
     */
    public function setAuthor($author)
    {
        $this->author = $author;
        return $this;
    }

    /**
     * Get the message of the acknowledged
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set the message of the acknowledged
     *
     * @param   string $message
     *
     * @return  $this
     */
    public function setMessage($message)
    {
        $this->message = $message;
        $this->hash = null;
        return $this;
    }

    /**
     * Get the start date and time of the acknowledged
     *
     * @return int
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Set the start date and time of the acknowledged
     *
     * @param   int $start
     *
     * @return  $this
     */
    public function setStart($start)
    {
        $this->start = $start;
        return $this;
    }

    /**
     * Get the end date and time of the acknowledged
     *
     * @return int
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * Set the end date and time of the acknowledged
     *
     * @param   int $end
     *
     * @return  $this
     */
    public function setEnd($end)
    {
        $this->end = $end;
        return $this;
    }

    /**
     * Get the hash of the acknowledgement
     *
     * @return string
     */
    public function getHash()
    {
        if ($this->hash === null) {
            $this->hash = md5($this->message);
        }
        return $this->hash;
    }
}
