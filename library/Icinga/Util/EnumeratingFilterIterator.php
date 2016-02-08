<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Util;

use FilterIterator;

/**
 * Class EnumeratingFilterIterator
 *
 * FilterIterator with continuous numeric key (index)
 */
abstract class EnumeratingFilterIterator extends FilterIterator
{
    /**
     * @var int
     */
    private $index;

    /**
     * @return void
     */
    public function rewind()
    {
        parent::rewind();
        $this->index = 0;
    }

    /**
     * @return int
     */
    public function key()
    {
        return $this->index++;
    }
}
