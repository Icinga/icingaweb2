<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Data;

/**
 * Interface for retrieving just a portion of a result set
 */
interface Limitable
{
    /**
     * Set a limit count and offset
     *
     * @param   int $count  Number of rows to return
     * @param   int $offset Start returning after this many rows
     *
     * @return  self
     */
    public function limit($count = null, $offset = null);

    /**
     * Whether a limit is set
     *
     * @return bool
     */
    public function hasLimit();

    /**
     * Get the limit if any
     *
     * @return int|null
     */
    public function getLimit();

    /**
     * Whether an offset is set
     *
     * @return bool
     */
    public function hasOffset();

    /**
     * Get the offset if any
     *
     * @return int|null
     */
    public function getOffset();
}
