<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Translation\Catalog;

use ArrayIterator;
use DateTime;
use Exception;
use IteratorAggregate;
use Icinga\Application\Benchmark;
use Icinga\Exception\IcingaException;
use Icinga\Web\FileCache;
use Icinga\Module\Translation\Exception\CatalogEntryException;
use Icinga\Module\Translation\Exception\CatalogException;
use Icinga\Module\Translation\Exception\CatalogHeaderException;

/**
 * Class Catalog
 *
 * Provides a convenient interface to handle gettext PO files.
 *
 * @package Icinga\Module\Translation\Catalog
 */
class Catalog implements IteratorAggregate
{
    /**
     * Header for this Catalog
     *
     * @var CatalogHeader
     */
    protected $header;

    /**
     * Entries for this Catalog
     *
     * @var array
     */
    protected $entries;

    /**
     * Create a new Catalog
     *
     * @param   CatalogHeader   $header
     * @param   array           $entries
     */
    public function __construct(CatalogHeader $header, array $entries)
    {
        $this->header = $header;
        $this->entries = $entries;
    }

    /**
     * Create and return a new Catalog from the given array of entries
     *
     * @param   array   $rawEntries
     *
     * @return  Catalog
     *
     * @throws  CatalogException
     */
    public static function fromArray(array $rawEntries)
    {
        Benchmark::measure('Catalog::fromArray()');

        $header = null;
        $entries = array();
        foreach ($rawEntries as $key => $rawEntry) {
            if (isset($rawEntry['msgid']) && empty($rawEntry['msgid'])) {
                $header = CatalogHeader::fromString($rawEntry['msgstr'][0]);
                if (isset($rawEntry['translator_comments'])) {
                    $header->setCopyrightInformation($rawEntry['translator_comments']);
                }
            } else {
                try {
                    $entries[] = CatalogEntry::fromArray($rawEntry);
                } catch (CatalogEntryException $e) {
                    throw $e->setEntryNumber($header ? $key : $key + 1);
                }
            }
        }

        if ($header === null) {
            throw new CatalogHeaderException('Header not found');
        }

        return new Catalog($header, $entries);
    }

    /**
     * Create and return a new Catalog from the given path
     *
     * @param   string  $catalogPath
     * @param   bool    $cache          Whether to utilize the cache or not
     *
     * @return  Catalog
     *
     * @throws  CatalogException
     */
    public static function fromPath($catalogPath, $cache = false)
    {
        Benchmark::measure('Catalog::fromPath()');

        if ($cache) {
            $catalog = static::fromCache($catalogPath);
            if ($catalog !== null) {
                return $catalog;
            }
        }

        try {
            $entries = CatalogParser::parsePath($catalogPath);
            $catalog = Catalog::fromArray($entries);
        } catch (CatalogHeaderException $e) {
            throw new CatalogException(
                'An exception occurred while reading "' . $catalogPath . '": ' . $e->getMessage()
            );
        } catch (CatalogEntryException $e) {
            throw new CatalogException(
                'Invalid entry #' . $e->getEntryNumber() . ' in "' . $catalogPath . '": ' . $e->getMessage()
            );
        }

        if ($cache) {
            static::cacheEntries($catalogPath, $entries);
        }

        return $catalog;
    }

    /**
     * Attempt to create and return a new Catalog from the given cached path
     *
     * @param   string  $catalogPath
     *
     * @return  Catalog|null            Null in case the path has not been cached yet
     */
    protected static function fromCache($catalogPath)
    {
        Benchmark::measure('Catalog::fromCache()');

        $eTag = FileCache::etagForFiles($catalogPath);
        $cacheFile = 'translation-' . $eTag . '.po.json';
        $cache = FileCache::instance();
        if ($cache->has($cacheFile)) {
            return static::fromArray(json_decode($cache->get($cacheFile), true));
        }
    }

    /**
     * Cache the given catalog entries
     *
     * @param   string  $catalogPath    The path to the po file which has initially been parsed
     * @param   array   $entries        The entries as produced by CatalogParser::parsePath()
     */
    protected static function cacheEntries($catalogPath, array $entries)
    {
        $eTag = FileCache::etagForFiles($catalogPath);
        $cacheFile = 'translation-' . $eTag . '.po.json';
        FileCache::instance()->store($cacheFile, json_encode($entries));
    }

    /**
     * Create and return a iterator for this catalogs entries
     *
     * @return  ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->entries);
    }

    /**
     * Return whether the given header exists
     *
     * @param   string  $name   The name of the header
     *
     * @return  bool
     */
    public function hasHeader($name)
    {
        return isset($this->header[$name]);
    }

    /**
     * Return the value of the given header
     *
     * @param   string  $name   The name of the header
     *
     * @return  string
     */
    public function getHeader($name)
    {
        return $this->header[$name];
    }

    /**
     * Set the given header to the given value
     *
     * @param   string  $name   The name of the header
     * @param   string  $value  The value of the header
     *
     * @return  $this
     */
    public function setHeader($name, $value)
    {
        $this->header[$name] = $value;
        return $this;
    }

    /**
     * Remove the given header
     *
     * @param   string  $name   The name of the header
     *
     * @return  $this
     */
    public function removeHeader($name)
    {
        unset ($this->header[$name]);
        return $this;
    }

    /**
     * Return the creation date of this Catalog
     *
     * @return  DateTime
     */
    public function creationDate()
    {
        return date_create_from_format(CatalogHeader::DATETIME_FORMAT, $this->getHeader('POT-Creation-Date'));
    }

    /**
     * Return the revision date of this Catalog
     *
     * @return  DateTime
     */
    public function revisionDate()
    {
        return date_create_from_format(CatalogHeader::DATETIME_FORMAT, $this->getHeader('PO-Revision-Date'));
    }

    /**
     * Render and return this catalog as a string
     *
     * @return  string
     */
    public function render()
    {
        $renderedCatalog = $this->header->render();
        foreach ($this->entries as $entry) {
            $renderedCatalog .= "\n\n" . $entry->render();
        }

        return $renderedCatalog;
    }

    /**
     * @see Catalog::render()
     */
    public function __toString()
    {
        try {
            return $this->render();
        } catch (Exception $e) {
            return 'Failed to render Catalog: ' . IcingaException::describe($e);
        }
    }
}
