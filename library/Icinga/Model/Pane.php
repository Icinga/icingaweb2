<?php

/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Model;

use ipl\Orm\Model;
use ipl\Orm\Relations;

class Pane extends Model
{
    public function getTableName()
    {
        return 'dashboard';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'name',
            'label'
        ];
    }

    public function getMetaData()
    {
        return [
            'name'  => t('Pane Name'),
            'label' => t('Pane Title')
        ];
    }

    public function getSearchColumns()
    {
        return ['name'];
    }

    public function getDefaultSort()
    {
        return 'dashboard.name';
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('dashboard_home', Home::class)->setCandidateKey('home_id');
        $relations->belongsTo('dashboard_member', PaneMember::class)->setCandidateKey('id');
    }
}
