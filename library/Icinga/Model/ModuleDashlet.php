<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Model;

use ipl\Orm\Model;

class ModuleDashlet extends Model
{
    public function getTableName()
    {
        return 'module_dashlet';
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

    public function getMetaData()
    {
        return [
            'name'          => t('Dashlet Name'),
            'label'         => t('Dashlet Title'),
            'module'        => t('Module Name'),
            'pane'          => t('Dashboard Pane Name'),
            'url'           => t('Dashlet Url'),
            'description'   => t('Dashlet Description'),
            'priority'      => t('Dashlet Order Priority')
        ];
    }

    public function getSearchColumns()
    {
        return ['name'];
    }

    public function getDefaultSort()
    {
        return ['module_dashlet.name', 'module_dashlet.priority'];
    }
}
