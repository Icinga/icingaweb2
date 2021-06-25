<?php
/* Icinga Web 2 | (c) 2018 Icinga Development Team | GPLv2+ */

namespace Icinga\Data\DataArray;

use Icinga\Data\SimpleQuery;

class ArrayQuery extends SimpleQuery
{
    /**
     * The current result
     *
     * @var array
     */
    protected $result;

    /**
     * Set the current result
     *
     * @param   array   $result
     *
     * @return  $this
     */
    public function setResult(array $result)
    {
        $this->result = $result;

        return $this;
    }

    /**
     * Return the current result
     *
     * @return  array|null
     */
    public function getResult()
    {
        return $this->result;
    }
}
