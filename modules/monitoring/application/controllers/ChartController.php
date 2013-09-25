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
use Icinga\Module\Monitoring\Backend;
use Icinga\Chart\Axis;

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
        $this->backend = Backend::getInstance($this->_getParam('backend'));
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
                'color' => 'black',
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
}