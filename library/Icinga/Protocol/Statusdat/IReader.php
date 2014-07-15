<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Protocol\Statusdat;

interface IReader
{
    /**
     * @return mixed
     */
    public function getState();

    /**
     * @param $type
     * @param $name
     * @return mixed
     */
    public function getObjectByName($type, $name);
}
