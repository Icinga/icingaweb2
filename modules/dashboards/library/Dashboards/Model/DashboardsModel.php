<?php

namespace Icinga\Module\Dashboards\Model;

use ipl\Orm\Model;

class DashboardsModel extends Model
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
        'title',
        'url',
        'priority',
        'style_width',
        'dashboard_id'
        ];
    }

    public function getDefaultSort()
    {
        return 'priority DESC';
    }
}