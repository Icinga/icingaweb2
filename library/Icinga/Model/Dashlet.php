<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Model;

use ipl\Orm\Model;
use ipl\Orm\Relations;
use ipl\Sql\Expression;

class Dashlet extends Model
{
    public function getTableName()
    {
        return 'dashlet';
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
            'priority'
        ];
    }

    public function getMetaData()
    {
        return [
            'dashboard_id' => t('Dashboard Id'),
            'name'         => t('Dashlet Name'),
            'label'        => t('Dashlet Title'),
            'url'          => t('Dashlet Url'),
            'priority'     => t('Dashlet Order Priority')
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

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('dashboard', Pane::class);
        //$relations->belongsTo('home', Home::class);

        $relations->belongsToMany('module_dashlet', ModuleDashlet::class)
            ->through(SystemDashlet::class)
            ->setJoinType('LEFT');
    }
}
