<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Hook;

abstract class DataviewExtensionHook
{
    public function getAdditionalQueryColumns($queryName)
    {
        $cols = $this->provideAdditionalQueryColumns($queryName);

        if (! is_array($cols)) {
            return array();
        }

        return $cols;
    }

    abstract public function provideAdditionalQueryColumns($queryName);
}
