<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Object;

use InvalidArgumentException;
use Traversable;
use Icinga\Util\StringHelper;

/**
 * Acknowledgement of a host or service incident
 */
class Acknowledgement
{
    /**
     * Author of the acknowledgement
     *
     * @var string
     */
    protected $author;

    /**
     * Comment of the acknowledgement
     *
     * @var string
     */
    protected $comment;

    /**
     * Entry time of the acknowledgement
     *
     * @var int
     */
    protected $entryTime;

    /**
     * Expiration time of the acknowledgment
     *
     * @var int|null
     */
    protected $expirationTime;

    /**
     * Whether the acknowledgement is sticky
     *
     * Sticky acknowledgements suppress notifications until the host or service recovers
     *
     * @var bool
     */
    protected $sticky = false;

    /**
     * Create a new acknowledgement of a host or service incident
     *
     * @param array|object|Traversable $properties
     *
     * @throws InvalidArgumentException If the type of the given properties is invalid
     */
    public function __construct($properties = null)
    {
        if ($properties !== null) {
            $this->setProperties($properties);
        }
    }

    /**
     * Get the author of the acknowledgement
     *
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set the author of the acknowledgement
     *
     * @param   string $author
     *
     * @return  $this
     */
    public function setAuthor($author)
    {
        $this->author = (string) $author;
        return $this;
    }

    /**
     * Get the comment of the acknowledgement
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set the comment of the acknowledgement
     *
     * @param   string $comment
     *
     * @return  $this
     */
    public function setComment($comment)
    {
        $this->comment = (string) $comment;

        return $this;
    }

    /**
     * Get the entry time of the acknowledgement
     *
     * @return int
     */
    public function getEntryTime()
    {
        return $this->entryTime;
    }

    /**
     * Set the entry time of the acknowledgement
     *
     * @param   int $entryTime
     *
     * @return  $this
     */
    public function setEntryTime($entryTime)
    {
        $this->entryTime = (int) $entryTime;

        return $this;
    }

    /**
     * Get the expiration time of the acknowledgement
     *
     * @return int|null
     */
    public function getExpirationTime()
    {
        return $this->expirationTime;
    }

    /**
     * Set the expiration time of the acknowledgement
     *
     * @param   int|null $expirationTime Unix timestamp
     *
     * @return  $this
     */
    public function setExpirationTime($expirationTime = null)
    {
        $this->expirationTime = $expirationTime !== null ? (int) $expirationTime : null;

        return $this;
    }

    /**
     * Get whether the acknowledgement is sticky
     *
     * @return bool
     */
    public function getSticky()
    {
        return $this->sticky;
    }

    /**
     * Set whether the acknowledgement is sticky
     *
     * @param   bool $sticky
     *
     * @return  $this
     */
    public function setSticky($sticky = true)
    {
        $this->sticky = (bool) $sticky;
        return $this;
    }

    /**
     * Get whether the acknowledgement expires
     *
     * @return bool
     */
    public function expires()
    {
        return $this->expirationTime !== null;
    }

    /**
     * Set the properties of the acknowledgement
     *
     * @param   array|object|Traversable $properties
     *
     * @return  $this
     * @throws  InvalidArgumentException If the type of the given properties is invalid
     */
    public function setProperties($properties)
    {
        if (! is_array($properties) && ! is_object($properties) && ! $properties instanceof Traversable) {
            throw new InvalidArgumentException('Properties must be either an array or an instance of Traversable');
        }
        foreach ($properties as $name => $value) {
            $setter = 'set' . ucfirst(StringHelper::cname($name));
            if (method_exists($this, $setter)) {
                $this->$setter($value);
            }
        }
        return $this;
    }
}
