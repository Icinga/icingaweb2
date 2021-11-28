<?php

/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Model;

use ipl\Orm\Model;
use ipl\Orm\Relations;

class DBUser extends Model
{
    public function getTableName()
    {
        return 'dashboard_user';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return ['name'];
    }

    public function getSearchColumns()
    {
        return ['name'];
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('home_member', HomeMember::class);
        $relations->belongsTo('dashboard_member', PaneMember::class);
    }
}
