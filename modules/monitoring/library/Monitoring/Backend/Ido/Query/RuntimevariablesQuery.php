<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

/**
 * Query for runtimevariables table
 */
class RuntimevariablesQuery extends IdoQuery
{
    protected $columnMap = array(
        'runtimevariables' => array(
            'id'        => 'runtimevariable_id',
            'varname'   => 'varname',
            'varvalue'  => 'varvalue'
        )
    );
}
