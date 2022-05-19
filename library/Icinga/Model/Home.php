<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Model;

use Icinga\Model\Behavior\BoolCast;
use Icinga\Web\Dashboard\DashboardHome;
use Icinga\Web\Dashboard;
use ipl\Orm\Behaviors;
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
            'user_id',
            'name',
            'label',
            'type',
            'priority',
            'disabled',
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'name'     => t('Dashboard Home Name'),
            'label'    => t('Dashboard Home Title'),
            'priority' => t('Dashboard Priority Order')
        ];
    }

    public function getSearchColumns()
    {
        return ['name'];
    }

    public function getDefaultSort()
    {
        return 'priority';
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new BoolCast(['disabled']));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('icingaweb_dashboard_owner', DashboardOwner::class)
            ->setCandidateKey('user_id');

        $relations->hasMany(Dashboard\Pane::TABLE, Pane::class);
        //$relations->hasMany(Dashboard\Dashlet::TABLE, Dashlet::class);
    }
}
