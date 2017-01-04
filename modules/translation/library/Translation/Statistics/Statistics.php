<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Translation\Statistics;

use Exception;
use Icinga\Exception\IcingaException;

/**
 * Class Statistics
 *
 * Creates statistics about a .po file
 */
class Statistics
{
    /**
     * The statistics' path
     *
     * @var string
     */
    protected $path;

    /**
     * The amount of entries
     *
     * @var int
     */
    protected $entryCount;

    /**
     * The amount of untranslated entries
     *
     * @var int
     */
    protected $untranslatedEntryCount;

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
     * Create a new Statistics object
     *
     * @param   string  $path   The path from which to create the statistics
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * Run msgfmt from the gettext tools and output the gathered statistics
     *
     * @return string
     */
    protected function getStatistics()
    {
        $line = '/usr/bin/msgfmt ' . $this->path . ' --statistics -cf';
        $descriptorSpec = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w')
        );
        $env = array('LANG' => 'en_GB');
        $process = proc_open(
            $line,
            $descriptorSpec,
            $pipes,
            null,
            $env,
            null
        );

        $info = stream_get_contents($pipes[2]);

        proc_close($process);

        return $info;
    }

    /**
     * Parse the gathered statistics from msgfmt of the gettext tools
     *
     * @throws  IcingaException     In case it's not possible to parse msgfmt's output
     */
    protected function sortNumbers()
    {
        $info = explode('msgfmt: found ', $this->getStatistics());
        $relevant = end($info);
        if ($relevant === false) {
            throw new IcingaException('Cannot parse the output given by msgfmt for path %s', $this->path);
        }

        preg_match_all('/\d+ [a-z]+/', $relevant , $results);
        foreach ($results[0] as $value) {
            $chunks = explode(' ', $value);
            switch ($chunks[1]) {
                case 'fatal':
                    $this->faultyEntryCount = (int)$chunks[0];
                    break;
                case 'translated':
                    $this->translatedEntryCount = (int)$chunks[0];
                    break;
                case 'fuzzy':
                    $this->fuzzyEntryCount = (int)$chunks[0];
                    break;
                case 'untranslated':
                    $this->untranslatedEntryCount = (int)$chunks[0];
                    break;
            }
        }

        $this->entryCount = $this->faultyEntryCount
            + $this->translatedEntryCount
            + $this->fuzzyEntryCount
            + $this->untranslatedEntryCount;
    }

    /**
     * Count all Entries of these statistics
     *
     * @return int
     */
    public function countEntries()
    {
        if ($this->entryCount === null) {
            $this->sortNumbers();
        }
        return $this->entryCount;
    }

    /**
     * Count all untranslated entries of these statistics
     *
     * @return int
     */
    public function countUntranslatedEntries()
    {
        if ($this->untranslatedEntryCount === null) {
            $this->sortNumbers();
        }
        return $this->untranslatedEntryCount;
    }

    /**
     * Count all translated entries of these statistics
     *
     * @return int
     */
    public function countTranslatedEntries()
    {
        if ($this->translatedEntryCount === null) {
            $this->sortNumbers();
        }
        return $this->translatedEntryCount;
    }

    /**
     * Count all fuzzy entries of these statistics
     *
     * @return int
     */
    public function countFuzzyEntries()
    {
        if ($this->fuzzyEntryCount === null) {
            $this->sortNumbers();
        }
        return $this->fuzzyEntryCount;
    }

    /**
     * Count all faulty entries of these statistics
     *
     * @return int
     */
    public function countFaultyEntries()
    {
        if ($this->faultyEntryCount === null) {
            $this->sortNumbers();
        }
        return $this->faultyEntryCount;
    }
}