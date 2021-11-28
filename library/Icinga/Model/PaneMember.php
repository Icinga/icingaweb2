<?php

/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Model;

use ipl\Orm\Model;
use ipl\Orm\Relations;

class PaneMember extends Model
{
    public function getTableName()
    {
        return 'dashboard_member';
    }

    public function getKeyName()
    {
        return 'dashboard_id';
    }

    public function getColumns()
    {
        return [
            'type',
            'owner',
            'write_access',
            'removed',
            'ctime',
            'mtime'
        ];
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('dashboard', Pane::class);
        $relations->belongsTo('dashboard_user', DBUser::class)
            ->setForeignKey('id')
            ->setCandidateKey('user_id');
        $relations->belongsToMany('dashboard_home', Home::class)
            ->through(Pane::class);
    }
}
