<?php

/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Model;

use ipl\Orm\Model;
use ipl\Orm\Relations;

class HomeMember extends Model
{
    public function getTableName()
    {
        return 'home_member';
    }

    public function getKeyName()
    {
        return ['home_id', 'user_id'];
    }

    public function getColumns()
    {
        return [
            'type',
            'owner',
            'disabled'
        ];
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('dashboard_home', Home::class);
        $relations->belongsTo('dashboard_user', DBUser::class)
            ->setCandidateKey('user_id');

        $relations->hasMany('dashboard', Pane::class)
            ->setForeignKey('home_id')
            ->setCandidateKey('home_id');
    }
}
