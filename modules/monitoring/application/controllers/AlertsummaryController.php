<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Chart\GridChart;
use Icinga\Chart\Unit\StaticAxis;
use Icinga\Module\Monitoring\Controller;
use Icinga\Module\Monitoring\Web\Widget\SelectBox;

class Monitoring_AlertsummaryController extends Controller
{
    public function indexAction()
    {
        $this->view->intervalBox = $this->createIntervalBox();
        $this->view->recentAlerts = $this->createRecentAlerts();
        $this->view->interval = $this->getInterval();
    }

    public function defectimageAction()
    {
        $gridChart = new GridChart();
        $interval = $this->getInterval();

        $gridChart->alignTopLeft();
        $gridChart->setAxisLabel('', t('Services'))
            ->setXAxis(new StaticAxis())
            ->setAxisMin(null, 0)
            ->setYAxis(new \Icinga\Chart\Unit\LinearUnit(10));

        $query = $this->backend->select()->from('notification', array(
            'host',
            'service',
            'notification_output',
            'notification_contact',
            'notification_start_time',
            'notification_state'
        ));

        $query->setFilter(
            new Icinga\Data\Filter\FilterExpression(
                'n.start_time',
                '>=',
                $this->getBeginDate($interval)->format('Y-m-d H:i:s')
            )
        );

        $query->order('notification_start_time', 'asc');


        $records    = $query->paginate(10000);
        $data       = array();
        $defects    = array();
        $period     = $this->createPeriod($interval);

        foreach ($period as $entry) {
            $id = $this->getPeriodFormat($interval, $entry->getTimestamp());
            $data[$id] = array($id, 0);
            $defects[$id] = array($id, 0);
        }

        foreach ($records as $item) {
            $id = $this->getPeriodFormat($interval, $item->notification_start_time);
            if (empty($data[$id])) {
                $data[$id] = array($id, 0);
            }

            $data[$id][1]++;
        }

        $gridChart->drawBars(
            array(
                'label' => $this->translate('Notifications'),
                'color' => 'green',
                'data'  =>  $data,
                'showPoints' => true
            )
        );

        $query      = null;
        $records    = null;
        $item       = null;

        $query = $this->backend->select()->from('eventhistory', array(
            'host_name',
            'service_description',
            'object_type',
            'timestamp',
            'state',
            'attempt',
            'max_attempts',
            'output',
            'type',
            'host',
            'service',
            'service_host_name'
        ));

        $query->addFilter(
            new Icinga\Data\Filter\FilterExpression(
                'timestamp',
                '>=',
                $this->getBeginDate($interval)->getTimestamp()
            )
        );

        $query->addFilter(
            new Icinga\Data\Filter\FilterExpression(
                'state',
                '>',
                0
            )
        );

        $records = $query->paginate(10000);

        foreach ($records as $item) {
            $id = $this->getPeriodFormat($interval, $item->timestamp);
            if (empty($data[$id])) {
                $defects[$id] = array($id, 0);
            }

            $defects[$id][1]++;
        }

        $gridChart->drawLines(
            array(
                'label' => $this->translate('Defects'),
                'color' => 'red',
                'data'  =>  $defects,
                'showPoints' => true
            )
        );

        $this->view->chart = $gridChart;
    }

    private function createRecentAlerts()
    {
        $query = $this->backend->select()->from('notification', array(
            'host',
            'service',
            'notification_output',
            'notification_contact',
            'notification_start_time',
            'notification_state'
        ));

        $query->order('notification_start_time', 'desc');

        return $query->paginate(5);
    }

    private function createIntervalBox()
    {
        $box = new SelectBox(
            'intervalBox',
            array(
                '1d' => t('One day'),
                '1w' => t('One week'),
                '1m' => t('One month'),
                '1y' => t('One year')
            ),
            t('Report interval'),
            'interval'
        );
        $box->applyRequest($this->getRequest());
        return $box;
    }

    private function getPeriodFormat($interval, $timestamp)
    {
        $format = '';
        if ($interval === '1d') {
            $format = '%H:00:00';
        } elseif ($interval === '1w') {
            $format = '%Y-%m-%d';
        } elseif ($interval === '1m') {
            $format = '%Y-%m-%d';
        } elseif ($interval === '1y') {
            $format = '%Y-%m';
        }

        return strftime($format, $timestamp);
    }

    private function createPeriod($interval)
    {
        if ($interval === '1d') {
            return new DatePeriod($this->getBeginDate($interval), new DateInterval('PT1H'), 24);
        } elseif ($interval === '1w') {
            return new DatePeriod($this->getBeginDate($interval), new DateInterval('P1D'), 7);
        } elseif ($interval === '1m') {
            return new DatePeriod($this->getBeginDate($interval), new DateInterval('P1D'), 30);
        } elseif ($interval === '1y') {
            return new DatePeriod($this->getBeginDate($interval), new DateInterval('P1M'), 12);
        }
    }

    private function getBeginDate($interval)
    {
        $new = new DateTime();
        if ($interval === '1d') {
            return $new->sub(new DateInterval('P1D'));
        } elseif ($interval === '1w') {
            return $new->sub(new DateInterval('P1W'));
        } elseif ($interval === '1m') {
            return $new->sub(new DateInterval('P1M'));
        } elseif ($interval === '1y') {
            return $new->sub(new DateInterval('P1Y'));
        }

        return null;
    }

    private function getInterval()
    {
        return $this->getParam('interval', '1d');
    }
} 