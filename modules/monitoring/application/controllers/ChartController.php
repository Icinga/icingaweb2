<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

use Icinga\Module\Monitoring\Controller;
use Icinga\Chart\GridChart;
use Icinga\Chart\PieChart;
use Icinga\Chart\Unit\StaticAxis;
use Icinga\Chart\Unit\LogarithmicUnit;
use Icinga\Chart\Unit\LinearUnit;

/**
 * Class Monitoring_CommandController
 *
 * Interface to send commands and display forms
 */

class Monitoring_ChartController extends Controller
{
    private function drawLogChart1()
    {
        $chart = new GridChart();
        $chart->alignTopLeft();
        $chart->setAxisLabel('X axis label', 'Y axis label')
            ->setYAxis(new LogarithmicUnit());

        for ($i = -15; $i < 15; $i++) {
            $data1[] = array($i, -$i * rand(1, 10) * pow(2, rand(1, 2)));
        }
        for ($i = -15; $i < 15; $i++) {
            $data2[] = array($i, 1000 + $i * rand(1, 35) * pow(2, rand(1, 2)));
        }
        for ($i = -15; $i < 15; $i++) {
            $data3[] = array($i, $i * rand(1, 100) * pow(2, rand(1, 10)) - 1000);
        }

        $chart->drawLines(
            array(
                'label' => 'Random 1',
                'color' => '#F56',
                'data'  =>  $data1,
                'showPoints' => true
            )
        );
        $chart->drawLines(
            array(
                'label' => 'Random 2',
                'color' => '#fa4',
                'data'  =>  $data2,
                'showPoints' => true
            )
        );
        $chart->drawLines(
            array(
                'label' => 'Random 3',
                'color' => '#4b7',
                'data'  =>  $data3,
                'showPoints' => true
            )
        );
        return $chart;
    }

    private function drawLogChart2()
    {
        $chart = new GridChart();
        $chart->alignTopLeft();
        $chart->setAxisLabel('X axis label', 'Y axis label')
            ->setYAxis(new LogarithmicUnit());

        for ($i = -10; $i < 10; $i++) {
            $sign = $i > 0 ?  1 :
                   ($i < 0 ? -1 : 0);
            $data[] = array($i, $sign * pow(10, abs($i)));
        }
        $chart->drawLines(
            array(
                'label' => 'f(x): sign(x) * 10^|x|',
                'color' => '#F56',
                'data'  =>  $data,
                'showPoints' => true
            )
        );
        return $chart;
    }
    private function drawLogChart3()
    {
        $chart = new GridChart();
        $chart->alignTopLeft();
        $chart->setAxisLabel('X axis label', 'Y axis label')
            ->setYAxis(new LogarithmicUnit());

        for ($i = -2; $i < 3; $i++) {
            $sign = $i > 0 ?  1 :
                ($i < 0 ? -1 : 0);
            $data[] = array($i, $sign * pow(10, abs($i)));
        }
        $chart->drawLines(
            array(
                'label' => 'f(x): sign(x) * 10^|x|',
                'color' => '#F56',
                'data'  =>  $data,
                'showPoints' => true
            )
        );
        return $chart;
    }

    public function testAction()
    {
        $this->chart = new GridChart();
        $this->chart->alignTopLeft();
        $this->chart->setAxisLabel('X axis label', 'Y axis label')->setXAxis(new StaticAxis());
        $data1 = array();
        $data2 = array();
        $data3 = array();
        for ($i = 0; $i < 50; $i++) {
            $data3[] = array('Label ' . $i, rand(0, 30));
        }

        /*
        $this->chart->drawLines(
            array(
                'label' => 'Nr of outtakes',
                'color' => '#F56',
                'width' => '5',

                'data'  => $data
            ), array(
                'label' => 'Some line',
                'color' => '#fa4',
                'width' => '4',

                'data'  =>  $data3,
                'showPoints' => true
            )
        );
*/
        $this->chart->drawBars(
            array(
                'label' => 'A big amount of data',
                'color' => '#4b7',
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
        $this->view->svgs = array();
        $this->view->svgs[] = $this->drawLogChart1();
        $this->view->svgs[] = $this->drawLogChart2();
        $this->view->svgs[] = $this->drawLogChart3();
        $this->view->svgs[] = $this->chart;
    }

    public function hostgroupAction()
    {
        $query = $this->backend->select()->from(
            'groupsummary',
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
        )->order('hostgroup')->getQuery()->fetchAll();
        $this->view->height = intval($this->getParam('height', 500));
        $this->view->width = intval($this->getParam('width', 500));
        if (count($query) === 1) {
            $this->drawHostGroupPie($query[0]);
        } else {
            $this->drawHostGroupChart($query);
        }
    }

    public function servicegroupAction()
    {
        $query = $this->backend->select()->from(
            'groupsummary',
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
        )->order('servicegroup')->getQuery()->fetchAll();
        $this->view->height = intval($this->getParam('height', 500));
        $this->view->width = intval($this->getParam('width', 500));


        if (count($query) === 1) {
            $this->drawServiceGroupPie($query[0]);
        } else {
            $this->drawServiceGroupChart($query);
        }
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
        $this->view->chart->title = $this->translate('Service Group Chart');
        $this->view->chart->description = $this->translate('Contains service states for each service group.');

        $this->view->chart->alignTopLeft();
        $this->view->chart->setAxisLabel('', $this->translate('Services'))
            ->setXAxis(new StaticAxis())
            ->setAxisMin(null, 0);

        $tooltip = $this->translate('<b>{title}:</b><br>{value} of {sum} services are {label}');
        $this->view->chart->drawBars(
            array(
                'label' => $this->translate('Ok'),
                'color' => '#44bb77',
                'stack' => 'stack1',
                'data'  => $okBars,
                'tooltip' => $tooltip
            ),
            array(
                'label' => $this->translate('Warning'),
                'color' => '#ffaa44',
                'stack' => 'stack1',
                'data'  => $warningBars,
                'tooltip' => $tooltip
            ),
            array(
                'label' => $this->translate('Critical'),
                'color' => '#ff5566',
                'stack' => 'stack1',
                'data'  => $critBars,
                'tooltip' => $tooltip
            ),
            array(
                'label' => $this->translate('Unknown'),
                'color' => '#dd66ff',
                'stack' => 'stack1',
                'data'  => $unknownBars,
                'tooltip' => $tooltip
            )
        );
    }

    private function drawHostGroupChart($query)
    {
        $upBars = array();
        $downBars = array();
        $unreachableBars = array();
        for ($i = 0; $i < 50; $i++) {
            $upBars[] = array(
                (string)$i, rand(1, 200), rand(1, 200)
            );
            $downBars[] = array(
                (string)$i, rand(1, 200), rand(1, 200)
            );
            $unreachableBars[] = array(
                (string)$i, rand(1, 200), rand(1, 200)
            );
        }
        $tooltip = $this->translate('<b>{title}:</b><br> {value} of {sum} hosts are {label}');
        $this->view->chart = new GridChart();
        $this->view->chart->title = $this->translate('Host Group Chart');
        $this->view->chart->description = $this->translate('Contains host states of each service group.');

        $this->view->chart->alignTopLeft();
        $this->view->chart->setAxisLabel('', $this->translate('Hosts'))
            ->setXAxis(new StaticAxis())
            ->setAxisMin(null, 0);
        $this->view->chart->drawBars(
            array(
                'label' => $this->translate('Up'),
                'color' => '#44bb77',
                'stack' => 'stack1',
                'data'  => $upBars,
                'tooltip' => $tooltip
            ),
            array(
                'label' => $this->translate('Down'),
                'color' => '#ff5566',
                'stack' => 'stack1',
                'data'  => $downBars,
                'tooltip' => $tooltip
            ),
            array(
                'label' => $this->translate('Unreachable'),
                'color' => '#dd66ff',
                'stack' => 'stack1',
                'data'  => $unreachableBars,
                'tooltip' => $tooltip
            )
        );
    }

    private function drawServiceGroupPie($query)
    {
        $this->view->chart = new PieChart();
        $this->view->chart->alignTopLeft();
        $this->view->chart->drawPie(array(
            'data' => array(
                (int) $query->services_ok,
                (int) $query->services_warning_unhandled,
                (int) $query->services_warning_handled,
                (int) $query->services_critical_unhandled,
                (int) $query->services_critical_handled,
                (int) $query->services_unknown_unhandled,
                (int) $query->services_unknown_handled,
                (int) $query->services_pending
            ),
            'colors' => array('#44bb77', '#ff4444', '#ff0000', '#ffff00', '#ffff33', '#E066FF', '#f099FF', '#fefefe'),
            'labels'=> array(
                $query->services_ok . ' Up Services',
                $query->services_warning_handled . $this->translate(' Warning Services (Handled)'),
                $query->services_warning_unhandled . $this->translate(' Warning Services (Unhandled)'),
                $query->services_critical_handled . $this->translate(' Down Services (Handled)'),
                $query->services_critical_unhandled . $this->translate(' Down Services (Unhandled)'),
                $query->services_unknown_handled . $this->translate(' Unreachable Services (Handled)'),
                $query->services_unknown_unhandled . $this->translate(' Unreachable Services (Unhandled)'),
                $query->services_pending . $this->translate(' Pending Services')
            )
        ));
    }

    private function drawHostGroupPie($query)
    {
        $this->view->chart = new PieChart();
        $this->view->chart->alignTopLeft();
        $this->view->chart->drawPie(array(
            'data' => array(
                (int) $query->hosts_up,
                (int) $query->hosts_down_handled,
                (int) $query->hosts_down_unhandled,
                (int) $query->hosts_unreachable_handled,
                (int) $query->hosts_unreachable_unhandled,
                (int) $query->hosts_pending
            ),
            'colors' => array(
                '#44bb77',   // 'Ok'
                '#ff4444',   // 'Warning'
                '#ff0000',   // 'WarningHandled'
                '#E066FF',
                '#f099FF',
                '#fefefe'
            ),
            'labels'=> array(
                (int) $query->hosts_up . $this->translate(' Up Hosts'),
                (int) $query->hosts_down_handled . $this->translate(' Down Hosts (Handled)'),
                (int) $query->hosts_down_unhandled . $this->translate(' Down Hosts (Unhandled)'),
                (int) $query->hosts_unreachable_handled . $this->translate(' Unreachable Hosts (Handled)'),
                (int) $query->hosts_unreachable_unhandled . $this->translate(' Unreachable Hosts (Unhandled)'),
                (int) $query->hosts_pending . $this->translate(' Pending Hosts')
            )
        ), array(
            'data' => array(
                (int) $query->services_ok,
                (int) $query->services_warning_unhandled,
                (int) $query->services_warning_handled,
                (int) $query->services_critical_unhandled,
                (int) $query->services_critical_handled,
                (int) $query->services_unknown_unhandled,
                (int) $query->services_unknown_handled,
                (int) $query->services_pending
            ),
            'colors' => array('#44bb77', '#ff4444', '#ff0000', '#ffff00', '#ffff33', '#E066FF', '#f099FF', '#fefefe'),
            'labels'=> array(
                $query->services_ok . $this->translate(' Up Services'),
                $query->services_warning_handled . $this->translate(' Warning Services (Handled)'),
                $query->services_warning_unhandled . $this->translate(' Warning Services (Unhandled)'),
                $query->services_critical_handled . $this->translate(' Down Services (Handled)'),
                $query->services_critical_unhandled . $this->translate(' Down Services (Unhandled)'),
                $query->services_unknown_handled . $this->translate(' Unreachable Services (Handled)'),
                $query->services_unknown_unhandled . $this->translate(' Unreachable Services (Unhandled)'),
                $query->services_pending . $this->translate(' Pending Services')
            )
        ));
    }
}
