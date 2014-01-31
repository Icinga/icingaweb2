<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
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
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
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
            ->setXAxis(new \Icinga\Chart\Unit\StaticAxis());
        $data1 = array();
        $data2 = array();
        $data3 = array();
        for ($i=0; $i<25; $i++) {

            $data3[] = array('Label ' . $i, rand(0,30));
        }

        /*
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
*/
        $this->chart->drawBars(
            array(
                'label' => 'Some other line',
                'color' => 'green',
                'data'  =>  $data3,
                'showPoints' => true
            )
        );
/*
        $this->chart->drawLines(
            array(
                'label' => 'Nr of outtakes',
                'color' => 'yellow',
                'width' => '5',
                'data'  => $data2
            )
        );
*/
        $this->view->svg = $this->chart;
    }

    public function hostgroupAction()
    {
        $query = GroupsummaryView::fromRequest(
            $this->_request,
            array(
                'hostgroup',
                'hosts_up',
                'hosts_unreachable_handled',
                'hosts_unreachable_unhandled',
                'hosts_down_handled',
                'hosts_down_unhandled',
                'hosts_pending',
                'services_ok',
                'services_unknown_handled',
                'services_unknown_unhandled',
                'services_critical_handled',
                'services_critical_unhandled',
                'services_warning_handled',
                'services_warning_unhandled',
                'services_pending'
            )
        )->getQuery()->fetchAll();
        $this->view->height = intval($this->getParam('height', 220));
        $this->view->width = intval($this->getParam('width', 520));
        if (count($query) === 1) {
            $this->drawGroupPie($query[0]);
        } else {
            $this->drawHostGroupChart($query);
        }
    }

    public function servicegroupAction()
    {
        $query = GroupsummaryView::fromRequest(
            $this->_request,
            array(
                'servicegroup',
                'services_ok',
                'services_unknown_handled',
                'services_unknown_unhandled',
                'services_critical_handled',
                'services_critical_unhandled',
                'services_warning_handled',
                'services_warning_unhandled',
                'services_pending'
            )
        )->getQuery()->fetchAll();
        $this->view->height = intval($this->getParam('height', 220));
        $this->view->width = intval($this->getParam('width', 520));

        $this->drawServiceGroupChart($query);

    }

    private function drawServiceGroupChart($query)
    {
        $okBars = array();
        $warningBars = array();
        $critBars = array();
        $unknownBars = array();
        foreach ($query as $servicegroup) {
            $okBars[] = array($servicegroup->servicegroup, $servicegroup->services_ok);
            $warningBars[] = array($servicegroup->servicegroup, $servicegroup->services_warning_unhandled);
            $critBars[] = array($servicegroup->servicegroup, $servicegroup->services_critical_unhandled);
            $unknownBars[] = array($servicegroup->servicegroup, $servicegroup->services_unknown_unhandled);
        }
        $this->view->chart = new GridChart();
        $this->view->chart->setAxisLabel("X axis label", "Y axis label")
            ->setXAxis(new \Icinga\Chart\Unit\StaticAxis());
        $this->view->chart->drawBars(
            array(
                'label' => 'Services ok',
                'color' => '#00ff00',
                'stack' => 'stack1',
                'data'  => $okBars
            ),
            array(
                'label' => 'Services warning',
                'color' => '#ffff00',
                'stack' => 'stack1',
                'data'  => $warningBars
            ),
            array(
                'label' => 'Services critical',
                'color' => '#ff0000',
                'stack' => 'stack1',
                'data'  => $critBars
            ),
            array(
                'label' => 'Services unknown',
                'color' => '#E066FF',
                'stack' => 'stack1',
                'data'  => $unknownBars
            )
        );
    }

    private function drawHostGroupChart($query)
    {
        $upBars = array();
        $downBars = array();
        $unreachableBars = array();
        foreach ($query as $hostgroup) {
            $upBars[] = array($hostgroup->hostgroup, $hostgroup->hosts_up);
            $downBars[] = array($hostgroup->hostgroup, $hostgroup->hosts_down_unhandled);
            $unreachableBars[] = array($hostgroup->hostgroup, $hostgroup->hosts_unreachable_unhandled);
        }
        $this->view->chart = new GridChart();
        $this->view->chart->setAxisLabel("X axis label", "Y axis label")
            ->setXAxis(new \Icinga\Chart\Unit\StaticAxis());
        $this->view->chart->drawBars(
            array(
                'label' => 'Hosts up',
                'color' => '#00ff00',
                'stack' => 'stack1',
                'data'  => $upBars
            ),
            array(
                'label' => 'Hosts down',
                'color' => '#ff0000',
                'stack' => 'stack1',
                'data'  => $downBars
            ),
            array(
                'label' => 'Hosts unreachable',
                'color' => '#E066FF',
                'stack' => 'stack1',
                'data'  => $unreachableBars
            )
        );
    }

    private function drawGroupPie($query)
    {
        $this->view->chart = new PieChart();
        if (isset($query->hosts_up)) {
            $this->view->chart->drawPie(array(
                'data' => array(
                 //   (int) $query->hosts_up,
                    (int) $query->hosts_down_handled,
                    (int) $query->hosts_down_unhandled,
                    (int) $query->hosts_unreachable_handled,
                    (int) $query->hosts_unreachable_unhandled,
                    (int) $query->hosts_pending
                ),
                'colors' => array( '#ff4444', '#ff0000', '#E066FF', '#f099FF', '#fefefe'),
                'labels'=> array(
      //              (int) $query->hosts_up . ' Up Hosts',
                    (int) $query->hosts_down_handled . ' Down Hosts (Handled)',
                    (int) $query->hosts_down_unhandled . ' Down Hosts (Unhandled)',
                    (int) $query->hosts_unreachable_handled . ' Unreachable Hosts (Handled)',
                    (int) $query->hosts_unreachable_unhandled . ' Unreachable Hosts (Unhandled)',
                    (int) $query->hosts_pending . ' Pending Hosts'
                )
            ),array(
            'data' => array(
           //     (int) $query->services_ok,
                (int) $query->services_warning_unhandled,
                (int) $query->services_warning_handled,
                (int) $query->services_critical_unhandled,
                (int) $query->services_critical_handled,
                (int) $query->services_unknown_unhandled,
                (int) $query->services_unknown_handled,
                (int) $query->services_pending
            ),
            'colors' => array('#ff4444', '#ff0000', '#ffff00', '#ffff33', '#E066FF', '#f099FF', '#fefefe'),
            'labels'=> array(
       //         $query->services_ok . ' Up Services',
                $query->services_warning_handled . ' Warning Services (Handled)',
                $query->services_warning_unhandled . ' Warning Services (Unhandled)',
                $query->services_critical_handled . ' Down Services (Handled)',
                $query->services_critical_unhandled . ' Down Services (Unhandled)',
                $query->services_unknown_handled . '  Unreachable Services (Handled)',
                $query->services_unknown_unhandled . '  Unreachable Services (Unhandled)',
                $query->services_pending . ' Pending Services',

              )
        ));
        }
    }
}