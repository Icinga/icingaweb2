<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Chart\GridChart;
use Icinga\Chart\Unit\StaticAxis;
use Icinga\Module\Monitoring\Controller;
use Icinga\Module\Monitoring\Web\Widget\SelectBox;
use Icinga\Web\Url;

class Monitoring_AlertsummaryController extends Controller
{
    protected $url;

    private $notificationData;

    private $problemData;

    public function init()
    {
        $tabs = $this->getTabs();
        if (in_array($this->_request->getActionName(), array(
            'alertsummary',
        ))) {
            $tabs->extend(new OutputFormat())->extend(new DashboardAction());
        }

        $this->url = Url::fromRequest();

        $this->notificationData = $this->createNotificationData();
        $this->problemData = $this->createProblemData();
    }

    protected function addTitleTab($action, $title = false)
    {
        $title = $title ?: ucfirst($action);
        $this->getTabs()->add($action, array(
            'title' => $title,
            // 'url' => Url::fromPath('monitoring/list/' . $action)
            'url' => $this->url
        ))->activate($action);
        $this->view->title = $title;
    }

    public function indexAction()
    {
        $this->addTitleTab('alertsummary');
        $this->view->intervalBox = $this->createIntervalBox();
        $this->view->recentAlerts = $this->createRecentAlerts();
        $this->view->interval = $this->getInterval();
        $this->view->defectChart = $this->createDefectImage();
        $this->view->perf = $this->createNotificationPerfdata();
        $this->view->trend = $this->createTrendInformation();

        $this->setAutorefreshInterval(15);
        $query = $this->backend->select()->from('notification', array(
            'host',
            'service',
            'notification_output',
            'notification_contact',
            'notification_start_time',
            'notification_state'
        ));

        $this->view->notifications = $query->paginate();
    }

    private function createNotificationData() {
        $interval = $this->getInterval();

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
        $period     = $this->createPeriod($interval);

        foreach ($period as $entry) {
            $id = $this->getPeriodFormat($interval, $entry->getTimestamp());
            $data[$id] = array($id, 0);
        }

        foreach ($records as $item) {
            $id = $this->getPeriodFormat($interval, $item->notification_start_time);
            if (empty($data[$id])) {
                $data[$id] = array($id, 0);
            }

            $data[$id][1]++;
        }

        return $data;
    }

    private function createTrendInformation()
    {
        $date = new DateTime();

        $beginDate = $date->sub(new DateInterval('P3D'));
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
                $beginDate->format('Y-m-d H:i:s')
            )
        );

        $query->order('notification_start_time', 'asc');

        $records = $query->paginate(10000);
        $slots = array();

        $period = new DatePeriod($beginDate, new DateInterval('P1D'), 2, DatePeriod::EXCLUDE_START_DATE);
        foreach ($period as $entry) {
            $slots[$entry->format('Y-m-d')] = 0;
        }

        foreach ($records as $item) {
            $id = strftime('%Y-%m-%d', $item->notification_start_time);
            if (isset($slots[$id])) {
                $slots[$id]++;
            }
        }

        $yesterday = array_shift($slots);
        $today = array_shift($slots);

        $out = new stdClass();
        if ($yesterday === $today) {
            $out->trend = 'unchanged';
        } elseif ($yesterday > $today) {
            $out->trend = 'down';
        } else {
            $out->trend = 'up';
        }

        if ($yesterday <= 0) {
            $out->percent = 100;
        } elseif ($yesterday === $today) {
            $out->percent = 0;
        } else {
            $out->percent = 100 -
                ((100/($yesterday > $today ? $yesterday : $today)) * ($yesterday > $today ? $today : $yesterday));
        }

        return $out;
    }

    private function createNotificationPerfdata()
    {
        $interval = $this->getInterval();

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

        $query->order('notification_start_time', 'desc');

        $records = $query->paginate(10000);
        $slots = array();

        foreach ($records as $item) {
            $id = strftime('%Y-%m-%d %H:%I:00', $item->notification_start_time);

            if (empty($slots[$id])) {
                $slots[$id] = 0;
            }

            $slots[$id]++;
        }

        $out = new stdClass();
        $out->avg = sprintf('%.2f', array_sum($slots) / count($slots));
        $out->last = array_shift($slots);

        return $out;
    }

    private function createProblemData()
    {
        $interval = $this->getInterval();

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

        $defects = array();
        $records = $query->paginate(10000);
        $period     = $this->createPeriod($interval);

        foreach ($period as $entry) {
            $id = $this->getPeriodFormat($interval, $entry->getTimestamp());
            $defects[$id] = array($id, 0);
        }

        foreach ($records as $item) {
            $id = $this->getPeriodFormat($interval, $item->timestamp);
            if (empty($defects[$id])) {
                $defects[$id] = array($id, 0);
            }
            $defects[$id][1]++;
        }

        return $defects;
    }

    public function createDefectImage()
    {
        $gridChart = new GridChart();
        $interval = $this->getInterval();

        $gridChart->alignTopLeft();
        $gridChart->setAxisLabel('', t('Services'))
            ->setXAxis(new StaticAxis())
            ->setAxisMin(null, 0)
            ->setYAxis(new \Icinga\Chart\Unit\LinearUnit(10));



        $gridChart->drawBars(
            array(
                'label' => $this->translate('Notifications'),
                'color' => 'green',
                'data'  =>  $this->notificationData,
                'showPoints' => true
            )
        );

        $gridChart->drawLines(
            array(
                'label' => $this->translate('Defects'),
                'color' => 'red',
                'data'  =>  $this->problemData,
                'showPoints' => true
            )
        );

        return $gridChart;
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