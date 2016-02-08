<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Data;

use ArrayIterator;
use FilterIterator;
use Zend_Db_Expr;

/**
 * Iterator over non-pseudo monitoring query columns
 */
class ColumnFilterIterator extends FilterIterator
{
    /**
     * Create a new ColumnFilterIterator
     *
     * @param array $columns
     */
    public function __construct(array $columns)
    {
        parent::__construct(new ArrayIterator($columns));
    }

    /**
     * {@inheritdoc}
     */
    public function accept()
    {
        $column = $this->current();
        return ! ($column instanceof Zend_Db_Expr || $column === '(NULL)');
    }
}
