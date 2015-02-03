<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

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
