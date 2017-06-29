<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Announcement;

use DateTime;
use Icinga\Data\ConfigObject;
use Icinga\Data\Filter\Filter;
use Icinga\Data\Filter\FilterAnd;
use Icinga\Data\SimpleQuery;
use Icinga\Repository\IniRepository;
use Icinga\Web\Announcement;

/**
 * A collection of announcements stored in an INI file
 */
class AnnouncementIniRepository extends IniRepository
{
    /**
     * {@inheritdoc}
     */
    protected $queryColumns = array('announcement' => array('id', 'author', 'message', 'hash', 'start', 'end'));

    /**
     * {@inheritdoc}
     */
    protected $triggers = array('announcement');

    /**
     * {@inheritDoc}
     */
    protected $configs = array('announcement' => array(
        'name'      => 'announcements',
        'keyColumn' => 'id'
    ));

    /**
     * {@inheritDoc}
     */
    protected $conversionRules = array('announcement' => array(
        'start' => 'timestamp',
        'end'   => 'timestamp'
    ));

    /**
     * Create a DateTime from a timestamp
     *
     * @param   string  $timestamp
     *
     * @return  DateTime|null
     */
    protected function retrieveTimestamp($timestamp)
    {
        if ($timestamp !== null) {
            $dateTime = new DateTime();
            $dateTime->setTimestamp($timestamp);
            return $dateTime;
        }
        return null;
    }

    /**
     * Get a DateTime's timestamp
     *
     * @param   DateTime    $datetime
     *
     * @return  int|null
     */
    protected function persistTimestamp(DateTime $datetime)
    {
        return $datetime === null ? null : $datetime->getTimestamp();
    }

    /**
     * Before-insert trigger (per row)
     *
     * @param   ConfigObject    $new    The original data to insert
     *
     * @return  ConfigObject            The eventually modified data to insert
     */
    protected function onInsertAnnouncement(ConfigObject $new)
    {
        if (! isset($new->id)) {
            $new->id = uniqid();
        }
        if (! isset($new->hash)) {
            $announcement = new Announcement($new->toArray());
            $new->hash = $announcement->getHash();
        }

        return $new;
    }

    /**
     * Before-update trigger (per row)
     *
     * @param   ConfigObject    $old    The original data as currently stored
     * @param   ConfigObject    $new    The original data to update
     *
     * @return  ConfigObject            The eventually modified data to update
     */
    protected function onUpdateAnnouncement(ConfigObject $old, ConfigObject $new)
    {
        if ($new->message !== $old->message) {
            $announcement = new Announcement($new->toArray());
            $new->hash = $announcement->getHash();
        }

        return $new;
    }

    /**
     * Get the ETag of the announcements.ini file
     *
     * @return  string
     */
    public function getEtag()
    {
        $file = $this->getDataSource('announcement')->getConfigFile();
        if (@is_readable($file)) {
            $mtime = filemtime($file);
            $size = filesize($file);
            return hash('crc32', $mtime . $size);
        }
        return null;
    }

    /**
     * Get the query for all active announcements
     *
     * @return  SimpleQuery
     */
    public function findActive()
    {
        $now = new DateTime();
        $query = $this
            ->select(array('hash', 'message'))
            ->setFilter(new FilterAnd(array(
                Filter::expression('start', '<=', $now),
                Filter::expression('end', '>=', $now)
            )))
            ->order('start');
        return $query;
    }

    /**
     * Get the timestamp of the next active announcement
     *
     * @return  int|null
     */
    public function findNextActive()
    {
        $now = new DateTime();
        $query = $this
            ->select(array('start', 'end'))
            ->setFilter(Filter::matchAny(array(
                Filter::expression('start', '>', $now), Filter::expression('end', '>', $now)
            )));
        $refresh = null;
        foreach ($query as $row) {
            $min = min($row->start->getTimestamp(), $row->end->getTimestamp());
            if ($refresh === null) {
                $refresh = $min;
            } else {
                $refresh = min($refresh, $min);
            }
        }
        return $refresh;
    }
}
