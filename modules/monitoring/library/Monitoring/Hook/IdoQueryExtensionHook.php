<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Hook;

use Icinga\Module\Monitoring\Backend\Ido\Query\IdoQuery;

abstract class IdoQueryExtensionHook
{
    abstract public function extendColumnMap(IdoQuery $query);

    public function joinVirtualTable(IdoQuery $query, $virtualTable)
    {
    }
}
