<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Data;

use Iterator;

/**
 * Interface for retrieving data
 */
interface Fetchable
{
    /**
     * Fetch and return all rows of the result set using an iterator
     *
     * @return  Iterator
     */
    public function fetch();

    /**
     * Retrieve an array containing all rows of the result set
     *
     * @return  array
     */
    public function fetchAll();

    /**
     * Fetch the first row of the result set
     *
     * @return  mixed
     */
    public function fetchRow();

    /**
     * Fetch a column of all rows of the result set as an array
     *
     * @param   int $columnIndex Index of the column to fetch
     *
     * @return  array
     */
    public function fetchColumn($columnIndex = 0);

    /**
     * Fetch the first column of the first row of the result set
     *
     * @return  string
     */
    public function fetchOne();

    /**
     * Fetch all rows of the result set as an array of key-value pairs
     *
     * The first column is the key, the second column is the value.
     *
     * @return  array
     */
    public function fetchPairs();
}
