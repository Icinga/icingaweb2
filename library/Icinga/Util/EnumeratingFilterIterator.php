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

    public function rewind(): void
    {
        parent::rewind();
        $this->index = 0;
    }

    public function key(): int
    {
        return $this->index++;
    }
}
