<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Translation\Exception;

/**
 * Class CatalogEntryException
 *
 * Will be thrown if catalog entry related errors are encountered.
 *
 * @package Icinga\Module\Translation\Exception
 */
class CatalogEntryException extends CatalogException
{
    /**
     * Number of the faulty entry
     *
     * @var int
     */
    protected $entryNumber;

    /**
     * Set the number of the faulty entry
     *
     * @param   int  $entryNumber
     *
     * @return  $this
     */
    public function setEntryNumber($entryNumber)
    {
        $this->entryNumber = $entryNumber;
        return $this;
    }

    /**
     * Return the number of the faulty entry
     *
     * @return  int
     */
    public function getEntryNumber()
    {
        return $this->entryNumber;
    }
}