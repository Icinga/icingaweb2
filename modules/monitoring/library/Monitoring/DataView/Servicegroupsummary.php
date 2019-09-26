<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

/**
 * Data view for service group summaries
 */
class Servicegroupsummary extends DataView
{
    public function getColumns()
    {
        return array(
            'servicegroup_alias',
            'servicegroup_name',
            'services_critical_handled',
            'services_critical_unhandled',
            'services_ok',
            'services_pending',
            'services_severity',
            'services_total',
            'services_unknown_handled',
            'services_unknown_unhandled',
            'services_warning_handled',
            'services_warning_unhandled'
        );
    }

    public function getSearchColumns()
    {
        return array('servicegroup', 'servicegroup_alias');
    }

    public function getSortRules()
    {
        return array(
            'servicegroup_alias' => array(
                'order' => self::SORT_ASC
            ),
            'services_severity' => array(
                'columns' => array(
                    'services_severity',
                    'servicegroup_alias ASC'
                ),
                'order' => self::SORT_DESC
            )
        );
    }

    public function getStaticFilterColumns()
    {
        return array(
            'instance_name',
            'services_severity',
            'host_contact', 'host_contactgroup', 'host_name',
            'hostgroup_name',
            'service_contact', 'service_contactgroup', 'service_description',
            'servicegroup'
        );
    }

    public function getFilterColumns()
    {
        if ($this->filterColumns === null) {
            $filterColumns = parent::getFilterColumns();
            $diff = array_diff($filterColumns, $this->getColumns());
            $this->filterColumns = array_merge($diff, [
                'Servicegroup Name'    => 'servicegroup_name',
                'Servicegroup Alias'   => 'servicegroup_alias'
            ]);
        }

        return $this->filterColumns;
    }
}
