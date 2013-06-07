<?php

// TODO: create interface instead of abstract class
namespace Icinga\Data;

interface DatasourceInterface
{
    /**
     * Instantiate a Query object
     *
     * @return AbstractQuery
     */
    public function select();
}
