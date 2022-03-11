<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Model;

use ipl\Orm\Model;
use ipl\Orm\Relations;

class SystemDashlet extends Model
{
    public function getTableName()
    {
        return 'dashlet_system';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'dashlet_id',
            'module_dashlet_id',
            'username'
        ];
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('dashlet', Dashlet::class);
        $relations->belongsTo('module_dashlet', ModuleDashlet::class);
    }
}
