<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Model;

use Icinga\Web\Dashboard\DashboardHome;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class DashboardOwner extends Model
{
    public function getTableName()
    {
        return 'icingaweb_dashboard_owner';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return ['username'];
    }

    public function createRelations(Relations $relations)
    {
        $relations->hasMany(DashboardHome::TABLE, Home::class);
    }
}
