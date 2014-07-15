<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

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
