<?php
/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Model;

use ipl\Orm\Model;
use ipl\Orm\Relations;

class ConfigOption extends Model
{
    public function getTableName()
    {
        return 'icingaweb_config_option';
    }

    public function getKeyName()
    {
        return [
            'scope_id',
            'name'
        ];
    }

    public function getColumns()
    {
        return [
            'scope_id',
            'name',
            'value'
        ];
    }

    public function getMetaData()
    {
        return [
            'name'  => t('Config Option Name'),
            'value' => t('Config Option Value')
        ];
    }

    public function getSearchColumns()
    {
        return ['name'];
    }

    public function getDefaultSort()
    {
        return 'icingaweb_config_option.name';
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('scope', ConfigScope::class)
            ->setCandidateKey('scope_id');
    }
}
