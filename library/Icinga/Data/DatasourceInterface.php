<?php

// TODO: create interface instead of abstract class
namespace Icinga\Data;

interface DatasourceInterface
{
    /**
     * Instantiate a Query object
     *
     * @return BaseQuery
     */
    public function select();
}
