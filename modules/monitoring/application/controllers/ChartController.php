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

use Icinga\Application\Icinga;
use Icinga\Application\Config;
use Icinga\Application\Logger;
use Icinga\Web\Form;
use Icinga\Web\Controller\ActionController;
use Icinga\Chart\SVGRenderer;
use Icinga\Chart\GridChart;
use Icinga\Module\Monitoring\DataView\Groupsummary as GroupsummaryView;
use Icinga\Module\Monitoring\Backend;
use Icinga\Chart\Palette;
use Icinga\Chart\Axis;
use Icinga\Chart\PieChart;

/**
 * Class Monitoring_CommandController
 *
 * Interface to send commands and display forms
 */

class Monitoring_ChartController extends ActionController
{
    /**
     * The backend used for this controller
     *
     * @var Backend
     */
    protected $backend;
    /**
     * Set to a string containing the compact layout name to use when
     * 'compact' is set as the layout parameter, otherwise null
     *
     * @var string
     */
    private $compactView;

    /**
     * Retrieve backend and hooks for this controller
     *
     * @see ActionController::init
     */
    public function init()
    {

    }

    public function testAction() {
        $this->chart = new GridChart();
        $this->chart->setAxisLabel("X axis label", "Y axis label")
            ->setAxisMin(null, 0);
        $data1 = array();
        $data2 = array();
        $data3 = array();
        for ($i=0; $i<25; $i++) {
            $data[] = array(1379344218+$i*10, rand(0,12));
            $data2[] = array(1379344218+$i*10, rand(4,30));
            $data3[] = array(1379344218+$i*10, rand(0,30));
        }
        $this->chart->drawLines(
            array(
                'label' => 'Nr of outtakes',
                'color' => 'red',
                'width' => '5',

                'data'  => $data
            ), array(
                'label' => 'Some line',
                'color' => 'blue',
                'width' => '4',

                'data'  =>  $data3,
                'showPoints' => true
            )
        );

        $this->chart->drawBars(
            array(
                'label' => 'Some other line',
                'color' => 'green',
                'data'  =>  $data3,
                'showPoints' => true
            )
        );

        $this->chart->drawLines(
            array(
                'label' => 'Nr of outtakes',
                'color' => 'yellow',
                'width' => '5',
                'data'  => $data2
            )
        );

        $this->view->svg = $this->chart;
    }

    public function hostgroupAction()
    {
        $query = GroupsummaryView::fromRequest(
            $this->_request,
            array(
                'hostgroup_name',
                'cnt_hosts_up',
                'cnt_hosts_unreachable',
                'cnt_hosts_unreachable_unhandled',
                'cnt_hosts_down',
                'cnt_hosts_down_unhandled',
                'cnt_hosts_pending',
                'cnt_services_ok',
                'cnt_services_unknown',
                'cnt_services_unknown_unhandled',
                'cnt_services_critical',
                'cnt_services_critical_unhandled',
                'cnt_services_warning',
                'cnt_services_warning_unhandled',
                'cnt_services_pending'
            )
        )->getQuery()->fetchRow();

        $this->view->chart = new PieChart();
        $this->view->chart->drawPie(
            array(
                'data' => array(
                    (int) $query->cnt_hosts_up,
                    (int) $query->cnt_hosts_down,
                    (int) $query->cnt_hosts_unreachable,
                    (int) $query->cnt_hosts_pending
                ),
                'colors' => array('00ff00', 'ff0000', 'ffff00', 'fefefe'),
                'labels'=> array(
                    (int) $query->cnt_hosts_up . ' Up Hosts',
                    (int) $query->cnt_hosts_down . ' Down Hosts',
                    (int) $query->cnt_hosts_unreachable . ' Unreachable Hosts',
                    (int) $query->cnt_hosts_pending . ' Pending Hosts'
                )
            ),
            array(
                'data' => array(
                    (int) $query->cnt_services_ok,
                    (int) $query->cnt_services_warning,
                    (int) $query->cnt_services_critical,
                    (int) $query->cnt_services_unknown,
                    (int) $query->cnt_services_pending
                ),
                'colors' => array('00ff00', 'ffff00','ff0000', 'efef00', 'fefefe'),
                'labels'=> array(
                    $query->cnt_services_ok . ' Up Services',
                    $query->cnt_services_warning . ' Warning Services',
                    $query->cnt_services_critical . ' Down Services',
                    $query->cnt_services_unknown . '  Unreachable Services',
                    $query->cnt_services_pending . ' Pending Services'
                )
            )

        );

    }
}