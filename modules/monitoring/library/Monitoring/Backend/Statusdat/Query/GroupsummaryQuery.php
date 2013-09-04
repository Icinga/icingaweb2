<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 * 
 * Icinga 2 Web - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 * 
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Backend\Statusdat\Query;

abstract class GroupsummaryQuery extends Query
{
    /**
     * @var mixed
     */
    protected $reader;

    /**
     * @var string
     */
    protected $groupType = "servicegroup";

    /**
     * @var string
     */
    protected $base = "services";

    /**
     * @var array
     */
    protected $available_columns = array(
        'ok' => 'SUM(CASE WHEN state = 0 THEN 1 ELSE 0 END)',
        'critical' => 'SUM(CASE WHEN state = 2 AND downtime = 0 AND ack = 0 THEN 1 ELSE 0 END)',
        'critical_dt' => 'SUM(CASE WHEN state = 2 AND downtime = 1 THEN 1 ELSE 0 END)',
        'critical_ack' => 'SUM(CASE WHEN state = 2 AND downtime = 0 AND ack = 1 THEN 1 ELSE 0 END)',
        'unknown' => 'SUM(CASE WHEN state = 3 AND downtime = 0 AND ack = 0 THEN 1 ELSE 0 END)',
        'unknown_dt' => 'SUM(CASE WHEN state = 3 AND downtime = 1 THEN 1 ELSE 0 END)',
        'unknown_ack' => 'SUM(CASE WHEN state = 3 AND downtime = 0 AND ack = 1 THEN 1 ELSE 0 END)',
        'warning' => 'SUM(CASE WHEN state = 1 AND downtime = 0 AND ack = 0 THEN 1 ELSE 0 END)',
        'warning_dt' => 'SUM(CASE WHEN state = 1 AND downtime = 1 THEN 1 ELSE 0 END)',
        'warning_ack' => 'SUM(CASE WHEN state = 1 AND downtime = 0 AND ack = 1 THEN 1 ELSE 0 END)',
    );

    /**
     * @var array
     */
    protected $order_columns = array(
        'state' => array(
            'ASC' => array(
                'ok ASC',
                'warning_dt ASC',
                'warning_ack ASC',
                'warning ASC',
                'unknown_dt ASC',
                'unknown_ack ASC',
                'unknown ASC',
                'critical_dt ASC',
                'critical_ack ASC',
                'critical ASC',
            ),
            'DESC' => array(
                'critical DESC',
                'critical_ack DESC',
                'critical_dt DESC',
                'unknown DESC',
                'unknown_ack DESC',
                'unknown_dt DESC',
                'warning DESC',
                'warning_ack DESC',
                'warning_dt DESC',
                'ok DESC',
            ),
            'default' => 'DESC'
        )
    );

    /**
     * @param $obj
     * @return string
     */
    private function getStateType(&$obj)
    {
        if ($obj->status->current_state == 0) {
            return "ok";
        }
        $typeBase = "";
        if ($obj->status->current_state == 1) {
            $typeBase = 'warning';
        } else {
            if ($obj->status->current_state == 2) {
                $typeBase = 'critical';
            } else {
                if ($obj->status->current_state == 3) {
                    $typeBase = 'unknown';
                }
            }
        }
        if ($obj->status->problem_has_been_acknowledged) {
            return $typeBase . "_ack";

        } else {
            if (isset($obj->status->downtime)) {
                return $typeBase . "_dt";
            }
        }
        return $typeBase;
    }

    /**
     * @param $indices
     * @return array
     */
    public function groupByProblemType(&$indices)
    {
        $typename = $this->groupType . "_name";
        $result = array();
        foreach ($indices as $type => $subIndices) {

            foreach ($subIndices as $objName) {

                $obj = $this->reader->getObjectByName($type, $objName);
                $statetype = $this->getStateType($obj);
                foreach ($obj->group as $group) {
                    if (!isset($result[$group])) {
                        $result[$group] = (object)array(
                            $typename => $group,
                            'ok' => 0,
                            'critical' => 0,
                            'critical_dt' => 0,
                            'critical_ack' => 0,
                            'unknown' => 0,
                            'unknown_dt' => 0,
                            'unknown_ack' => 0,
                            'warning' => 0,
                            'warning_dt' => 0,
                            'warning_ack' => 0
                        );
                    }
                    $result[$group]->$statetype++;
                }
            }
        }

        return array_values($result);
    }

    /**
     * @var \Icinga\Protocol\Statusdat\Query
     * @return mixed|void
     */
    public function init()
    {
        $this->reader = $this->ds->getReader();
        $this->query = $this->reader->select()->from($this->base, array())->groupByFunction(
            "groupByProblemType",
            $this
        )->where("COUNT{group} > 0");

    }

    /**
     * @param The $column
     * @param null $value
     * @return $this|Query
     */
    public function where($column, $value = null)
    {
        if ($column === 'problems') {
            if ($value === 'true') {
                //$this->where(
                //    "COUNT{downtime} == 0 AND status.problem_has_been_acknowledged == 0 AND status.current_state > 0"
                // );
            }
        } elseif ($column === 'search') {
            if ($value) {
                $this->where($this->name_alias . ' LIKE ?', '%' . $value . '%');
            }
        } else {
            parent::where($column, $value);
        }
        return $this;
    }
}
