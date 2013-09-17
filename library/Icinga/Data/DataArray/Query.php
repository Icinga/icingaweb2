<?php

namespace Icinga\Data\DataArray;

use Icinga\Data\AbstractQuery;

class Query extends AbstractQuery
{
    /**
     * Remember the last count
     */
    protected $count;

    /**
     * Remember the last result without applied limits
     */
    protected $result;

    public function getCount()
    {
        return $this->count;
    }

    public function hasResult()
    {
        return $this->result !== null;
    }

    public function getFullResult()
    {
        return $this->result;
    }

    public function getLimitedResult()
    {
        if ($this->hasLimit()) {
            if ($this->hasOffset()) {
                $offset = $this->getOffset();
            } else {
                $offset = 0;
            }
            return array_slice($this->result, $offset, $this->getLimit());
        } else {
            return $this->result;
        }
    }

    public function setResult($result)
    {
        $this->result = $result;
        $this->count  = count($result);
        return $this;
    }

    /**
     * ArrayDatasource will apply this function to sort the array
     *
     * @param mixed $a       Left side comparsion value
     * @param mixed $b       Right side comparsion value
     * @param int   $col_num Current position in order_columns array
     *
     * @return int
     */
    public function compare(& $a, & $b, $col_num = 0)
    {
        if (! array_key_exists($col_num, $this->order_columns)) {
            return 0;
        }
        $col = $this->order_columns[$col_num][0];
        $dir = $this->order_columns[$col_num][1];

        //$res = strnatcmp(strtolower($a->$col), strtolower($b->$col));
        $res = strcmp(strtolower($a->$col), strtolower($b->$col));
        if ($res === 0) {
            if (array_key_exists(++$col_num, $this->order_columns)) {
                return $this->compare($a, $b, $col_num);
            } else {
                return 0;
            }
        }
        if ($dir === self::SORT_ASC) {
            return $res;
        } else {
            return $res * -1;
        }
    }
}
