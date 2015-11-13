<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Hook;

abstract class DataviewExtensionHook
{
    abstract public function getAdditionalQueryColumns($queryName);
}
