<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Application\Icinga;
use Icinga\Application\Config;
use Icinga\Logger\Logger;
use Icinga\Web\Form;
use Icinga\Module\Monitoring\Controller;
use Icinga\Chart\SVGRenderer;
use Icinga\Chart\GridChart;
use Icinga\Chart\Palette;
use Icinga\Chart\Axis;
use Icinga\Chart\PieChart;
use Icinga\Chart\Unit\StaticAxis;

/**
 * Class Monitoring_CommandController
 *
 * Interface to send commands and display forms
 */

class Monitoring_ChartController extends Controller
{
    public function testAction()
    {
        $this->chart = new GridChart();
        $this->chart->setAxisLabel('X axis label', 'Y axis label')->setXAxis(new StaticAxis());
        $data1 = array();
        $data2 = array();
        $data3 = array();
        for ($i = 0; $i < 25; $i++) {
            $data3[] = array('Label ' . $i, rand(0, 30));
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
        $this->view->chart->setAxisLabel('', t('Services'))
            ->setXAxis(new \Icinga\Chart\Unit\StaticAxis());

        $this->view->chart->drawBars(
            array(
                'label' => t('Ok'),
                'color' => '#44bb77',
                'stack' => 'stack1',
                'data'  => $okBars
            ),
            array(
                'label' => t('Warning'),
                'color' => '#ffaa44',
                'stack' => 'stack1',
                'data'  => $warningBars
            ),
            array(
                'label' => t('Critical'),
                'color' => '#ff5566',
                'stack' => 'stack1',
                'data'  => $critBars
            ),
            array(
                'label' => t('Unknown'),
                'color' => '#dd66ff',
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
            $upBars[] = array(
                $hostgroup->hostgroup,
                $hostgroup->hosts_up
            );
            $downBars[] = array(
                $hostgroup->hostgroup,
                $hostgroup->hosts_down_unhandled
            );
            $unreachableBars[] = array(
                $hostgroup->hostgroup,
                $hostgroup->hosts_unreachable_unhandled
            );
        }
        $this->view->chart = new GridChart();
        $this->view->chart->setAxisLabel('', t('Hosts'))->setXAxis(new StaticAxis());
        $this->view->chart->drawBars(
            array(
                'label' => t('Up'),
                'color' => '#44bb77',
                'stack' => 'stack1',
                'data'  => $upBars
            ),
            array(
                'label' => t('Down'),
                'color' => '#ff5566',
                'stack' => 'stack1',
                'data'  => $downBars
            ),
            array(
                'label' => t('Unreachable'),
                'color' => '#dd66ff',
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
                    (int) $query->hosts_down_handled . t(' Down Hosts (Handled)'),
                    (int) $query->hosts_down_unhandled . t(' Down Hosts (Unhandled)'),
                    (int) $query->hosts_unreachable_handled . t(' Unreachable Hosts (Handled)'),
                    (int) $query->hosts_unreachable_unhandled . t(' Unreachable Hosts (Unhandled)'),
                    (int) $query->hosts_pending . t(' Pending Hosts')
                )
            ), array(
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
                $query->services_warning_handled . t(' Warning Services (Handled)'),
                $query->services_warning_unhandled . t(' Warning Services (Unhandled)'),
                $query->services_critical_handled . t(' Down Services (Handled)'),
                $query->services_critical_unhandled . t(' Down Services (Unhandled)'),
                $query->services_unknown_handled . t(' Unreachable Services (Handled)'),
                $query->services_unknown_unhandled . t(' Unreachable Services (Unhandled)'),
                $query->services_pending . t(' Pending Services')
              )
        ));
        }
    }
}
