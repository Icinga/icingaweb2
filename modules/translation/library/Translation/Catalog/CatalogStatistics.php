<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Translation\Catalog;

/**
 * Class CatalogStatistics
 *
 * Provides Catalog statistics.
 *
 * @package Icinga\Module\Translation\Catalog
 */
class CatalogStatistics
{
    /**
     * The Catalog for which to produce the statistics
     *
     * @var Catalog
     */
    protected $catalog;

    /**
     * The amount of entries
     *
     * @var int
     */
    protected $entryCount;

    /**
     * The amount of obsolete entries
     *
     * @var int
     */
    protected $obsoleteEntryCount;

    /**
     * The amount of translated entries
     *
     * @var int
     */
    protected $translatedEntryCount;

    /**
     * The amount of fuzzy entries
     *
     * @var int
     */
    protected $fuzzyEntryCount;

    /**
     * The amount of faulty entries
     *
     * @var int
     */
    protected $faultyEntryCount;

    /**
     * Create new CatalogStatistics
     *
     * @param   Catalog     $catalog
     */
    public function __construct(Catalog $catalog)
    {
        $this->catalog = $catalog;
    }

    /**
     * Return the amount of entries
     *
     * @return int
     */
    public function countEntries()
    {
        if ($this->entryCount === null) {
            $this->refresh();
        }

        return $this->entryCount;
    }

    /**
     * Return the amount of obsolete entries
     *
     * @return int
     */
    public function countObsoleteEntries()
    {
        if ($this->obsoleteEntryCount === null) {
            $this->refresh();
        }

        return $this->obsoleteEntryCount;
    }

    /**
     * Return the amount of translated entries
     *
     * @return int
     */
    public function countTranslatedEntries()
    {
        if ($this->translatedEntryCount === null) {
            $this->refresh();
        }

        return $this->translatedEntryCount;
    }

    /**
     * Return the amount of fuzzy entries
     *
     * @return int
     */
    public function countFuzzyEntries()
    {
        if ($this->fuzzyEntryCount === null) {
            $this->refresh();
        }

        return $this->fuzzyEntryCount;
    }

    /**
     * Return the amount of faulty entries
     *
     * @return int
     */
    public function countFaultyEntries()
    {
        if ($this->faultyEntryCount === null) {
            $this->refresh();
        }

        return $this->faultyEntryCount;
    }

    /**
     * Refresh statistic counters
     *
     * @return  $this
     */
    public function refresh()
    {
        $this->entryCount = 0;
        $this->obsoleteEntryCount = 0;
        $this->translatedEntryCount = 0;
        $this->fuzzyEntryCount = 0;
        $this->faultyEntryCount = 0;
        /** @var CatalogEntry $entry */
        foreach ($this->catalog as $entry) {
            $this->entryCount++;

            if ($entry->isObsolete()) {
                $this->obsoleteEntryCount++;
            }

            if ($entry->isTranslated()) {
                $this->translatedEntryCount++;
            }

            if ($entry->isFuzzy()) {
                $this->fuzzyEntryCount++;
            }

            if ($entry->isFaulty()) {
                $this->faultyEntryCount++;
            }
        }

        return $this;
    }
}
