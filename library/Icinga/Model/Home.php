<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Model;

use Icinga\Web\Dashboard\DashboardHome;
use Icinga\Web\Dashboard;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Home extends Model
{
    public function getTableName()
    {
        return DashboardHome::TABLE;
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'name',
            'label',
            'username',
            'type',
            'priority',
        ];
    }

    public function getMetaData()
    {
        return [
            'name'     => t('Dashboard Home Name'),
            'label'    => t('Dashboard Home Title'),
            'priority' => t('Dashboard Order Priority')
        ];
    }

    public function getSearchColumns()
    {
        return ['name'];
    }

    public function getDefaultSort()
    {
        return 'name';
    }

    public function createRelations(Relations $relations)
    {
        $relations->hasMany(Dashboard\Pane::TABLE, Pane::class);

        //$relations->hasMany(Dashboard\Dashlet::TABLE, Dashlet::class);
    }
}
