<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Announcement;

use Icinga\Web\Cookie;

/**
 * Handle acknowledged announcements via cookie
 */
class AnnouncementCookie extends Cookie
{
    /**
     * Array of hashes representing acknowledged announcements
     *
     * @var string[]
     */
    protected $acknowledged = array();

    /**
     * ETag of the last known announcements.ini
     *
     * @var string
     */
    protected $etag;

    /**
     * Timestamp of the next active acknowledgement, if any
     *
     * @var int|null
     */
    protected $nextActive;

    /**
     * AnnouncementCookie constructor
     */
    public function __construct()
    {
        parent::__construct('icingaweb2-announcements');
        $this->setExpire(2147483648);
        if (isset($_COOKIE['icingaweb2-announcements'])) {
            $cookie = json_decode($_COOKIE['icingaweb2-announcements'], true);
            if ($cookie !== null) {
                if (isset($cookie['acknowledged'])) {
                    $this->setAcknowledged($cookie['acknowledged']);
                }
                if (isset($cookie['etag'])) {
                    $this->setEtag($cookie['etag']);
                }
                if (isset($cookie['next'])) {
                    $this->setNextActive($cookie['next']);
                }
            }
        }
    }

    /**
     * Get the hashes of the acknowledged announcements
     *
     * @return  string[]
     */
    public function getAcknowledged()
    {
        return $this->acknowledged;
    }

    /**
     * Set the hashes of the acknowledged announcements
     *
     * @param   string[] $acknowledged
     *
     * @return  $this
     */
    public function setAcknowledged(array $acknowledged)
    {
        $this->acknowledged = $acknowledged;
        return $this;
    }

    /**
     * Get the ETag
     *
     * @return  string
     */
    public function getEtag()
    {
        return $this->etag;
    }

    /**
     * Set the ETag
     *
     * @param   string $etag
     *
     * @return  $this
     */
    public function setEtag($etag)
    {
        $this->etag = $etag;
        return $this;
    }

    /**
     * Get the timestamp of the next active announcement
     *
     * @return  int
     */
    public function getNextActive()
    {
        return $this->nextActive;
    }

    /**
     * Set the timestamp of the next active announcement
     *
     * @param   int $nextActive
     *
     * @return  $this
     */
    public function setNextActive($nextActive)
    {
        $this->nextActive = $nextActive;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        return json_encode(array(
            'acknowledged'  => $this->getAcknowledged(),
            'etag'          => $this->getEtag(),
            'next'          => $this->getNextActive()
        ));
    }
}
