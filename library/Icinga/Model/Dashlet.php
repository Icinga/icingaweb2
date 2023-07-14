<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Model;

use Icinga\Model\Behavior\BoolCast;
use Icinga\Web\Dashboard;
use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Dashlet extends Model
{
    public function getTableName()
    {
        return Dashboard\Dashlet::TABLE;
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'dashboard_id',
            'name',
            'label',
            'url',
            'priority',
            'disabled',
            'description'
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'dashboard_id' => t('Dashboard Id'),
            'name'         => t('Dashlet Name'),
            'label'        => t('Dashlet Title'),
            'url'          => t('Dashlet Url'),
            'priority'     => t('Dashlet Priority Order'),
            'description'  => t('Dashlet Description')
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

        $behaviors->add(new Binary(['id', 'dashboard_id']));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo(Dashboard\Pane::TABLE, Pane::class)
            ->setCandidateKey('dashboard_id');

        $relations->belongsToMany('icingaweb_module_dashlet', ModuleDashlet::class)
            ->through(SystemDashlet::class)
            ->setForeignKey('dashlet_id')
            ->setJoinType('LEFT');
    }
}
