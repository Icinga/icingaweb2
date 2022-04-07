<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Model;

use Icinga\Web\Dashboard;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Pane extends Model
{
    public function getTableName()
    {
        return Dashboard\Pane::TABLE;
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'home_id',
            'name',
            'label',
            'username',
            'priority'
        ];
    }

    public function getMetaData()
    {
        return [
            'home_id'  => t('Dashboard Home Id'),
            'name'     => t('Dashboard Name'),
            'label'    => t('Dashboard Title'),
            'username' => t('Username'),
        ];
    }

    public function getSearchColumns()
    {
        return ['name'];
    }

    public function getDefaultSort()
    {
        return 'icingaweb_dashboard.name';
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo(Dashboard\DashboardHome::TABLE, Home::class)
            ->setCandidateKey('home_id');

        $relations->hasMany(Dashboard\Dashlet::TABLE, Dashlet::class)
            ->setForeignKey('dashboard_id')
            ->setJoinType('LEFT');
    }
}
