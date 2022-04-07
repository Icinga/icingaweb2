<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Model;

use Icinga\Web\Dashboard;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class ModuleDashlet extends Model
{
    public function getTableName()
    {
        return 'icingaweb_module_dashlet';
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
            'module',
            'pane',
            'url',
            'description',
            'priority'
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'name'        => t('Dashlet Name'),
            'label'       => t('Dashlet Title'),
            'module'      => t('Module Name'),
            'pane'        => t('Pane Name'),
            'url'         => t('Dashlet Url'),
            'description' => t('Dashlet Description'),
            'priority'    => t('Dashlet Priority Order')
        ];
    }

    public function getSearchColumns()
    {
        return ['name'];
    }

    public function getDefaultSort()
    {
        return ['name', 'priority'];
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsToMany(Dashboard\Dashlet::TABLE, Dashlet::class)
            ->through(SystemDashlet::class)
            ->setForeignKey('module_dashlet_id')
            ->setJoinType('LEFT');
    }
}
