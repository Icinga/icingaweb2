<?php

/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Model;

use ipl\Orm\Model;
use ipl\Orm\Relations;

class Home extends Model
{
    public function getTableName()
    {
        return 'dashboard_home';
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
            'name'  => t('Home Name'),
            'label' => t('Home Title')
        ];
    }

    public function getSearchColumns()
    {
        return ['name'];
    }

    public function getDefaultSort()
    {
        return 'dashboard_home.name';
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('home_member', HomeMember::class)
            ->setCandidateKey('id');

        $relations->hasMany('dashboard', Pane::class);
    }
}
